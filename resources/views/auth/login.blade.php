<x-layouts.public>
    <section class="mx-auto flex min-h-[calc(100vh-180px)] max-w-md items-center px-6 py-16">
        <div class="w-full rounded-3xl border border-white/10 bg-white/[0.04] p-8 shadow-2xl shadow-black/20 sm:p-10">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-amber-300">Área privada</p>
            <h1 class="mt-4 text-3xl font-semibold text-white">Entre no seu feed</h1>
            <p class="mt-3 text-sm leading-6 text-slate-400">O cadastro é controlado pelo administrador nesta primeira versão.</p>

            @if (session('status'))
                <div class="mt-6 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm font-medium text-emerald-300">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                @csrf
                <div>
                    <label for="email" class="text-sm font-medium text-slate-200">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white outline-none ring-amber-300 transition placeholder:text-slate-600 focus:border-amber-300 focus:ring-2">
                    @error('email')<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="text-sm font-medium text-slate-200">Senha</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-3 text-white outline-none ring-amber-300 transition focus:border-amber-300 focus:ring-2">
                    @error('password')<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-400"><input type="checkbox" name="remember" class="rounded border-white/20 bg-slate-900 text-amber-300"> Lembrar de mim</label>
                <button type="submit" class="w-full rounded-xl bg-amber-300 px-4 py-3 font-semibold text-slate-950 transition hover:bg-amber-200">Entrar</button>
            </form>
        </div>
    </section>
</x-layouts.public>
