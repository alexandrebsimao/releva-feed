<x-layouts.authenticated>
    <section class="mx-auto max-w-6xl px-6 py-10 lg:py-14">
        <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-600">Administração</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Usuários</h1>
                <p class="mt-3 max-w-2xl text-slate-500">Cadastre e gerencie o acesso ao sistema. O cadastro é restrito a administradores.</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                Cadastrar usuário
            </a>
        </div>

        @if (session('status'))
            <div class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mt-8 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total de usuários</p>
                <p class="mt-2 text-3xl font-semibold text-slate-950">{{ $totals['all'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pendentes de confirmação</p>
                <p class="mt-2 text-3xl font-semibold text-amber-600">{{ $totals['pending'] }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Administradores</p>
                <p class="mt-2 text-3xl font-semibold text-slate-950">{{ $totals['admins'] }}</p>
            </div>
        </div>

        <div class="mt-10 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-slate-950">Todos os usuários</h2>
                    <p class="mt-1 text-sm text-slate-500">Usuários não confirmados só conseguem navegar após clicar no link recebido por e-mail.</p>
                </div>
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center gap-2">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Buscar por nome ou e-mail..." class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-200">
                    <button type="submit" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">Buscar</button>
                    @if ($search !== '')
                        <a href="{{ route('admin.users.index') }}" class="text-xs font-medium text-slate-500 hover:text-slate-950">Limpar</a>
                    @endif
                </form>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <article class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0 flex items-center gap-4">
                            <span class="inline-flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-slate-900 text-sm font-bold text-amber-300">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-slate-950">{{ $user->name }}</span>
                                    @if ($user->is_admin)
                                        <span class="rounded-full bg-slate-900 px-2.5 py-0.5 text-xs font-semibold text-amber-300">Admin</span>
                                    @endif
                                    @if ($user->hasVerifiedEmail())
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Confirmado</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Aguardando confirmação</span>
                                    @endif
                                    @if ($user->is(auth()->user()))
                                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">Você</span>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-sm text-slate-500 truncate">{{ $user->email }}</p>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-3 text-sm">
                            @if (! $user->hasVerifiedEmail())
                                <form method="POST" action="{{ route('admin.users.resend-verification', $user) }}">
                                    @csrf
                                    <button type="submit" class="rounded-xl border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-900 hover:bg-amber-100">
                                        Reenviar confirmação
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('admin.users.edit', $user) }}" class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                Editar
                            </a>

                            @if (! $user->is(auth()->user()))
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Deseja realmente excluir o usuário {{ $user->name }}? Esta ação não pode ser desfeita.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                        Excluir
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="px-6 py-12 text-center text-sm text-slate-500">
                        @if ($search !== '')
                            Nenhum usuário encontrado para a busca "{{ $search }}".
                        @else
                            Nenhum usuário cadastrado.
                        @endif
                    </p>
                @endforelse
            </div>

            @if ($users->hasPages())
                <div class="border-t border-slate-100 px-6 py-4">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </section>
</x-layouts.authenticated>
