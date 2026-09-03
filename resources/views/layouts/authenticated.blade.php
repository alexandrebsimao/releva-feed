<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('icons/icon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon.svg') }}">
    <title>{{ $title ?? 'Meu feed · '.config('app.name') }}</title>
    @if (file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 sm:py-5">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 text-lg font-semibold tracking-tight">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 font-black text-amber-300">R</span>
                <span class="hidden sm:inline">Releva Feed</span>
            </a>
            <div class="flex items-center gap-3 text-sm">
                <nav class="hidden items-center gap-4 sm:flex" aria-label="Navegação principal">
                    <a href="{{ route('dashboard') }}" class="font-medium text-slate-600 transition hover:text-slate-950">Meu feed</a>
                    <a href="{{ route('preferences.index') }}" class="font-medium text-slate-600 transition hover:text-slate-950">Preferências</a>
                    <span class="text-slate-500">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="font-medium text-slate-600 transition hover:text-slate-950">Sair</button>
                    </form>
                </nav>

                <details class="relative sm:hidden">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 font-semibold text-slate-700 shadow-sm [&::-webkit-details-marker]:hidden">
                        <span class="sr-only">Abrir navegação</span>
                        <span aria-hidden="true" class="text-lg leading-none">☰</span>
                        <span>Menu</span>
                    </summary>
                    <nav class="absolute right-0 z-40 mt-2 min-w-52 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-xl" aria-label="Navegação mobile">
                        <a href="{{ route('dashboard') }}" class="block rounded-xl px-3 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Meu feed</a>
                        <a href="{{ route('preferences.index') }}" class="block rounded-xl px-3 py-2.5 font-medium text-slate-700 hover:bg-slate-50">Preferências</a>
                        <p class="border-t border-slate-100 px-3 pb-1 pt-3 text-xs text-slate-400">{{ auth()->user()->name }}</p>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 pt-1">
                            @csrf
                            <button class="block w-full rounded-xl px-3 py-2.5 text-left font-medium text-slate-700 hover:bg-slate-50">Sair</button>
                        </form>
                    </nav>
                </details>
            </div>
        </div>
    </header>

    <main>{{ $slot }}</main>
    @livewireScripts
</body>
</html>
