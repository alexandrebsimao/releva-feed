@csrf
@if ($user->exists)
    @method('PUT')
@endif

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="name" class="text-sm font-medium text-slate-700">Nome completo</label>
        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
        @error('name')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="email" class="text-sm font-medium text-slate-700">E-mail</label>
        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
        @error('email')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        @if ($user->exists && $user->email_verified_at)
            <p class="mt-1 text-xs text-emerald-600">E-mail confirmado em {{ $user->email_verified_at->format('d/m/Y H:i') }}. Ao alterar o e-mail, será exigida nova confirmação.</p>
        @elseif ($user->exists && ! $user->email_verified_at)
            <p class="mt-1 text-xs text-amber-600">E-mail pendente de confirmação.</p>
        @endif
    </div>

    <div>
        <label for="password" class="text-sm font-medium text-slate-700">
            Senha {{ $user->exists ? '(opcional)' : '' }}
        </label>
        <input id="password" name="password" type="password" {{ $user->exists ? '' : 'required' }} autocomplete="new-password" placeholder="{{ $user->exists ? 'Deixe em branco para manter' : 'Mínimo de 8 caracteres' }}" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
        @error('password')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="password_confirmation" class="text-sm font-medium text-slate-700">Confirmar senha</label>
        <input id="password_confirmation" name="password_confirmation" type="password" {{ $user->exists ? '' : 'required' }} autocomplete="new-password" placeholder="Repita a senha" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
    </div>

    <div class="sm:col-span-2 pt-2">
        <label class="flex items-center gap-3 text-sm font-medium text-slate-700">
            <input type="checkbox" name="is_admin" value="1" @checked(old('is_admin', $user->is_admin)) class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-300">
            <span>Acesso de administrador (pode gerenciar fontes, usuários e visualizar métricas)</span>
        </label>
        @error('is_admin')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-6">
    <a href="{{ route('admin.users.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 hover:text-slate-950">Cancelar</a>
    <button type="submit" class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
        {{ $user->exists ? 'Salvar alterações' : 'Cadastrar usuário' }}
    </button>
</div>
