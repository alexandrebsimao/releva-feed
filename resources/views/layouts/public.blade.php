<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Uma leitura mais intencional das notícias que importam.">
    <title>{{ $title ?? config('app.name') }}</title>
    @if (file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="min-h-screen">
    <header class="border-b border-white/10">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
            <a href="{{ route('home') }}" class="flex items-center gap-3 text-lg font-semibold tracking-tight">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-300 font-black text-slate-950">R</span>
                <span>Releva Feed</span>
            </a>
            <a href="{{ route('login') }}" class="rounded-full border border-white/15 px-4 py-2 text-sm font-medium text-slate-200 transition hover:border-amber-300 hover:text-amber-200">Entrar</a>
        </div>
    </header>

    <main>{{ $slot }}</main>

    <footer class="mx-auto max-w-6xl px-6 py-10 text-sm text-slate-500">Curadoria pessoal, sem ruído.</footer>
    @livewireScripts
</body>
</html>
