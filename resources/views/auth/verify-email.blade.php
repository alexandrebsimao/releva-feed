<x-layouts.public>
    <section class="mx-auto flex min-h-[calc(100vh-180px)] max-w-md items-center px-6 py-16">
        <div class="w-full rounded-3xl border border-white/10 bg-white/[0.04] p-8 shadow-2xl shadow-black/20 sm:p-10">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-300">Confirmação de conta</p>
            <h1 class="mt-4 text-3xl font-semibold text-white">Confirme seu e-mail</h1>
            
            <p class="mt-3 text-sm leading-6 text-slate-300">
                Enviamos um link de confirmação para o endereço <strong class="text-white font-medium">{{ auth()->user()->email }}</strong>.
                Por favor, verifique sua caixa de entrada (ou pasta de spam) e clique no link para ativar seu acesso.
            </p>

            @if (session('status'))
                <div class="mt-6 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-medium text-emerald-300">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mt-8 space-y-4">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-xl bg-amber-300 px-4 py-3 font-semibold text-slate-950 transition hover:bg-amber-200">
                        Reenviar e-mail de confirmação
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="text-center">
                    @csrf
                    <button type="submit" class="text-sm text-slate-400 hover:text-white transition">
                        Sair da conta
                    </button>
                </form>
            </div>
        </div>
    </section>
</x-layouts.public>
