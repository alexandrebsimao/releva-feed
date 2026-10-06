<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fafafa">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('icons/icon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon.svg') }}">
    <title>{{ $title ?? 'Meu feed · '.config('app.name') }}</title>
    @if (file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="app-shell min-h-screen bg-[#fafafa] text-slate-900">
    @php($userInitial = strtoupper(substr(trim(auth()->user()->name), 0, 1)))

    <aside class="desktop-sidebar" aria-label="Navegação principal">
        <a href="{{ route('dashboard') }}" class="brand-lockup">
            <span class="brand-mark">R</span>
            <span class="brand-wordmark">Releva <em>Feed</em></span>
        </a>

        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" @class(['sidebar-link', 'is-active' => request()->routeIs('dashboard')])>
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="m3 10.7 9-7.2 9 7.2v8.1a2.2 2.2 0 0 1-2.2 2.2H5.2A2.2 2.2 0 0 1 3 18.8v-8.1Z" stroke="currentColor" stroke-width="1.8"/><path d="M9.2 21v-6.2h5.6V21" stroke="currentColor" stroke-width="1.8"/></svg>
                <span>Meu feed</span>
            </a>
            <a href="{{ route('radar') }}" @class(['sidebar-link', 'is-active' => request()->routeIs('radar')])>
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8.7" stroke="currentColor" stroke-width="1.8"/><path d="m12 12 5.3-5.3M12 8.5v3.6h3.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="12" r="1.7" fill="currentColor"/></svg>
                <span>Acontecimentos</span>
            </a>
            <a href="{{ route('preferences.index') }}" @class(['sidebar-link', 'is-active' => request()->routeIs('preferences.*') || request()->routeIs('my-topics.*')])>
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M12 3.6v2.1M12 18.3v2.1M20.4 12h-2.1M5.7 12H3.6M17.9 6.1l-1.5 1.5M7.6 16.4l-1.5 1.5M17.9 17.9l-1.5-1.5M7.6 7.6 6.1 6.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="12" r="3.6" stroke="currentColor" stroke-width="1.8"/></svg>
                <span>Preferências</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            @if (auth()->user()->is_admin)
                <a href="{{ route('admin.users.index') }}" @class(['sidebar-link', 'sidebar-link-small', 'is-active' => request()->routeIs('admin.users.*')])>
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <span>Usuários</span>
                </a>
                <a href="{{ route('admin.sources.index') }}" @class(['sidebar-link', 'sidebar-link-small', 'is-active' => request()->routeIs('admin.sources.*')])>
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h16M4 18h7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <span>Fontes</span>
                </a>
                <a href="{{ route('admin.observability') }}" @class(['sidebar-link', 'sidebar-link-small', 'is-active' => request()->routeIs('admin.observability')])>
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M4 19.5h16M6.5 17V9.5M12 17V5.5M17.5 17v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                    <span>Operação</span>
                </a>
            @endif
            <div class="sidebar-account">
                <span class="user-avatar user-avatar-sm">{{ $userInitial }}</span>
                <span class="min-w-0"><strong>{{ auth()->user()->name }}</strong><small>Seu perfil</small></span>
                <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                    @csrf
                    <button type="submit" class="icon-button" aria-label="Sair">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M14 5h4a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-4M10 8l-4 4 4 4M6 12h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <header class="mobile-topbar">
        <a href="{{ route('dashboard') }}" class="brand-lockup">
            <span class="brand-mark">R</span>
            <span class="brand-wordmark">Releva <em>Feed</em></span>
        </a>
        <details class="mobile-account-menu">
            <summary class="user-avatar user-avatar-sm">{{ $userInitial }}<span class="sr-only">Abrir menu da conta</span></summary>
            <div class="mobile-account-popover">
                <p>{{ auth()->user()->name }}</p>
                <a href="{{ route('preferences.index') }}">Preferências</a>
                @if (auth()->user()->is_admin)
                    <a href="{{ route('admin.users.index') }}">Usuários</a>
                    <a href="{{ route('admin.observability') }}">Operação</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sair</button></form>
            </div>
        </details>
    </header>

    <main class="app-content">{{ $slot }}</main>

    <nav class="mobile-bottom-nav" aria-label="Navegação mobile">
        <a href="{{ route('dashboard') }}" @class(['mobile-nav-link', 'is-active' => request()->routeIs('dashboard')])>
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="m3 10.7 9-7.2 9 7.2v8.1a2.2 2.2 0 0 1-2.2 2.2H5.2A2.2 2.2 0 0 1 3 18.8v-8.1Z" stroke="currentColor" stroke-width="1.8"/><path d="M9.2 21v-6.2h5.6V21" stroke="currentColor" stroke-width="1.8"/></svg><span>Feed</span>
        </a>
        <a href="{{ route('radar') }}" @class(['mobile-nav-link', 'is-active' => request()->routeIs('radar')])>
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8.7" stroke="currentColor" stroke-width="1.8"/><path d="m12 12 5.3-5.3M12 8.5v3.6h3.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="12" r="1.7" fill="currentColor"/></svg><span>Radar</span>
        </a>
        <a href="{{ route('preferences.index') }}" @class(['mobile-nav-link', 'is-active' => request()->routeIs('preferences.*') || request()->routeIs('my-topics.*')])>
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.1" stroke="currentColor" stroke-width="1.8"/><path d="M5.3 19.4a6.7 6.7 0 0 1 13.4 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><span>Perfil</span>
        </a>
    </nav>
    @livewireScripts
</body>
</html>
