<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', __('admin.title'))</title>
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#0d172b">

    @vite([
        'resources/css/admin.css',
        'resources/css/admin-cms.css',
        'resources/css/admin-multilang.css',
        'resources/css/admin-media.css',
        'resources/css/admin-shop.css',
        'resources/css/admin-orders.css',
        'resources/css/admin-settings.css',
        'resources/css/admin-gallery.css',
        'resources/css/admin-archive.css',
        'resources/css/admin-ai-translator.css',
        'resources/css/admin-discovery.css',
        'resources/css/admin-orchestrator.css',
        'resources/css/admin-contextual-recommendations.css',
        'resources/js/admin-cms.js',
        'resources/js/admin-multilang.js',
        'resources/js/admin-media.js',
        'resources/js/admin-shop.js',
        'resources/js/archive-admin.js'
    ])
</head>
<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <img
                    src="{{ asset('images/logo-okulary-3d.svg') }}"
                    alt="Wortal Okulary 3D"
                    width="160"
                    height="40"
                >
                <span>ADMIN</span>
            </div>

            <nav class="admin-nav" aria-label="{{ __('admin.navigation') }}">
                <a class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <span>⌂</span>
                    {{ __('admin.menu.dashboard') }}
                </a>

                <a class="admin-nav-link {{ request()->routeIs('admin.articles.*') ? 'is-active' : '' }}" href="{{ route('admin.articles.index') }}">
                    <span>✎</span>
                    {{ __('admin.menu.articles') }}
                </a>

                <a class="admin-nav-link {{ request()->routeIs('admin.article-categories.*') ? 'is-active' : '' }}" href="{{ route('admin.article-categories.index') }}">
                    <span>≡</span>
                    {{ __('admin.menu.categories') }}
                </a>

                <a class="admin-nav-link {{ request()->routeIs('admin.media.*') ? 'is-active' : '' }}" href="{{ route('admin.media.index') }}">
                    <span>▧</span>
                    {{ __('media.menu') }}
                </a>

                @if (in_array(auth()->user()->role, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN], true))
                    <a
                        class="admin-nav-link {{ request()->routeIs('admin.products.*') || request()->routeIs('admin.product-categories.*') ? 'is-active' : '' }}"
                        href="{{ route('admin.products.index') }}"
                    >
                        <span>▣</span>
                        {{ __('admin.menu.shop') }}
                    </a>

                    <a
                        class="admin-nav-link {{ request()->routeIs('admin.orders.*') ? 'is-active' : '' }}"
                        href="{{ route('admin.orders.index') }}"
                    >
                        <span>▤</span>
                        {{ __('cart.admin.orders') }}
                    </a>

                    <a class="admin-nav-link {{ request()->routeIs('admin.users') ? 'is-active' : '' }}" href="{{ route('admin.users') }}">
                        <span>◎</span>
                        {{ __('admin.menu.users') }}
                    </a>
                @endif



                <a
                    class="admin-nav-link {{ request()->routeIs('admin.archive.*') ? 'is-active' : '' }}"
                    href="{{ route('admin.archive.index') }}"
                >
                    <span>▧</span>
                    {{ __('archive.admin.menu') }}
                </a>

                <a
                    class="admin-nav-link {{ request()->routeIs('admin.gallery.*') ? 'is-active' : '' }}"
                    href="{{ route('admin.gallery.index') }}"
                >
                    <span>◫</span>
                    {{ __('gallery.admin.menu') }}
                </a>

                <a class="admin-nav-link {{ request()->routeIs('admin.translations*') ? 'is-active' : '' }}" href="{{ route('admin.translations') }}">
                    <span>文</span>
                    {{ __('admin.menu.translations') }}
                </a>

                @if (in_array(auth()->user()->role, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN], true))
                    <a class="admin-nav-link {{ request()->routeIs('admin.discovery.*') ? 'is-active' : '' }}" href="{{ route('admin.discovery.index') }}">
                        <span>⌕</span>
                        {{ __('discovery.admin.menu') }}
                    </a>

                    <a
                        class="admin-nav-link {{ request()->routeIs('admin.orchestrator.*') ? 'is-active' : '' }}"
                        href="{{ route('admin.orchestrator.index') }}"
                    >
                        <span>◇</span>
                        {{ __('orchestrator.admin.menu') }}
                    </a>
                @endif

                <a class="admin-nav-link {{ request()->routeIs('admin.analytics') ? 'is-active' : '' }}" href="{{ route('admin.analytics') }}">
                    <span>↗</span>
                    {{ __('admin.menu.analytics') }}
                </a>

                @if (auth()->user()->role === \App\Models\User::ROLE_SUPER_ADMIN)
                    <a class="admin-nav-link {{ request()->routeIs('admin.settings') ? 'is-active' : '' }}" href="{{ route('admin.settings') }}">
                        <span>⚙</span>
                        {{ __('admin.menu.settings') }}
                    </a>
                @endif
            </nav>

            <div class="admin-sidebar-footer">
                <a href="{{ route('home', ['locale' => config('locales.default', 'pl')]) }}">
                    ← {{ __('admin.back_to_portal') }}
                </a>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <div>
                    <span class="admin-topbar-kicker">{{ __('admin.panel') }}</span>
                    <strong>@yield('page_heading', __('admin.menu.dashboard'))</strong>
                </div>

                <div class="admin-user">
                    <div class="admin-user-copy">
                        <strong>{{ auth()->user()->name }}</strong>
                        <span>{{ __('portal_auth.roles.' . auth()->user()->role) }}</span>
                    </div>

                    <a
                        class="admin-account-link"
                        href="{{ route('account', ['locale' => config('locales.default', 'pl')]) }}"
                    >
                        {{ __('admin.account') }}
                    </a>

                    <form method="post" action="{{ route('logout', ['locale' => config('locales.default', 'pl')]) }}">
                        @csrf
                        <button type="submit">{{ __('admin.logout') }}</button>
                    </form>
                </div>
            </header>

            <main class="admin-content">
                @if (session('status'))
                    <div class="admin-flash admin-flash-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="admin-flash admin-flash-error">
                        <strong>{{ __('admin.validation_error') }}</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
