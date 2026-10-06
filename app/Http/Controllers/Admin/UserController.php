<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.mb_strtolower($search).'%';
                $query->where(fn ($q) => $q
                    ->whereRaw('lower(name) like ?', [$term])
                    ->orWhereRaw('lower(email) like ?', [$term]));
            })
            ->orderByDesc('is_admin')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'totals' => [
                'all' => User::query()->count(),
                'pending' => User::query()->whereNull('email_verified_at')->count(),
                'admins' => User::query()->where('is_admin', true)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', ['user' => new User]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_admin' => $data['is_admin'] ?? false,
        ]);

        return $this->redirectAfterSendingVerification(
            $user,
            "Usuário {$user->name} cadastrado. Enviamos um e-mail de confirmação para {$user->email}.",
        );
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $emailChanged = $data['email'] !== $user->email;

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_admin' => $data['is_admin'] ?? false,
        ]);

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        if ($emailChanged) {
            // A new address must be confirmed again before the account can be used.
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            return $this->redirectAfterSendingVerification(
                $user,
                "Usuário atualizado. Enviamos um novo e-mail de confirmação para {$user->email}.",
            );
        }

        return redirect()->route('admin.users.index')->with('status', 'Usuário atualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Você não pode excluir a sua própria conta.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', "Usuário {$name} excluído.");
    }

    public function resendVerification(User $user): RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return back()->with('status', 'Este e-mail já está confirmado.');
        }

        return $this->redirectAfterSendingVerification(
            $user,
            "E-mail de confirmação reenviado para {$user->email}.",
        );
    }

    /**
     * The account is saved even if the mail provider fails, so the admin can retry from the list.
     */
    private function redirectAfterSendingVerification(User $user, string $successMessage): RedirectResponse
    {
        $redirect = redirect()->route('admin.users.index');

        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            Log::error('Falha ao enviar e-mail de verificação.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);

            return $redirect->with('error', "Usuário salvo, mas não foi possível enviar o e-mail de confirmação para {$user->email}. Verifique a configuração de e-mail e use \"Reenviar confirmação\".");
        }

        return $redirect->with('status', $successMessage);
    }
}
