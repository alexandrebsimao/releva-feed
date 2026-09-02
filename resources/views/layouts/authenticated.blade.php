<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard · '.config('app.name') }}</title>
    @if (file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 text-lg font-semibold tracking-tight">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 font-black text-amber-300">R</span>
                <span>Releva Feed</span>
            </a>
            <div class="flex items-center gap-4 text-sm">
                <a href="{{ route('dashboard') }}" class="hidden font-medium text-slate-600 transition hover:text-slate-950 sm:inline">Dashboard</a>
                <a href="{{ route('sources.index') }}" class="hidden font-medium text-slate-600 transition hover:text-slate-950 sm:inline">Fontes</a>
                <a href="{{ route('articles.index') }}" class="hidden font-medium text-slate-600 transition hover:text-slate-950 sm:inline">Artigos</a>
                <a href="{{ route('radar') }}" class="hidden font-medium text-slate-600 transition hover:text-slate-950 sm:inline">Meu radar</a>
                <a href="{{ route('topics.index') }}" class="hidden font-medium text-slate-600 transition hover:text-slate-950 sm:inline">Assuntos</a>
                <a href="{{ route('my-topics.index') }}" class="hidden font-medium text-slate-600 transition hover:text-slate-950 sm:inline">Meus assuntos</a>
                @can('admin')<a href="{{ route('admin.observability') }}" class="hidden font-medium text-slate-600 transition hover:text-slate-950 sm:inline">Administração</a>@endcan
                <span class="hidden text-slate-500 sm:inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="font-medium text-slate-600 transition hover:text-slate-950">Sair</button>
                </form>
            </div>
        </div>
    </header>

    <main>{{ $slot }}</main>
    @livewireScripts
</body>
</html>
