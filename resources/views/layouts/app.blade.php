<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="portal-analytics-endpoint" content="{{ route('analytics.event') }}">
    <meta name="portal-route-name" content="{{ request()->route()?->getName() }}">

    @php
        $seoData = $pageSeo
            ?? app(\App\Services\SeoService::class)->current();
        $seoCanonical = $seoData['canonical'] ?? url()->current();
        $seoAlternates = $seoData['alternates'] ?? [];
        $seoXDefault = $seoData['x_default'] ?? null;
        $seoRobots = $seoData['robots'] ?? 'index,follow';
        $seoType = $seoData['type'] ?? 'website';
        $seoImage = $seoData['image'] ?? null;
        $seoOgLocale = $seoData['og_locale'] ?? app()->getLocale();
        $seoOgLocaleAlternates = $seoData['og_locale_alternates'] ?? [];
        $seoSchemas = $seoData['schemas'] ?? [];
    @endphp

    <title>@yield('title', __('site.title'))</title>
    <meta
        name="description"
        content="@yield('meta_description', __('site.meta_description'))"
    >
    <meta name="robots" content="{{ $seoRobots }}">

    <link rel="canonical" href="{{ $seoCanonical }}">

    @foreach ($seoAlternates as $locale => $alternateUrl)
        <link
            rel="alternate"
            hreflang="{{ $locale }}"
            href="{{ $alternateUrl }}"
        >
    @endforeach

    @if ($seoXDefault)
        <link
            rel="alternate"
            hreflang="x-default"
            href="{{ $seoXDefault }}"
        >
    @endif

    <meta property="og:type" content="{{ $seoType }}">
    <meta
        property="og:title"
        content="@yield('title', __('site.title'))"
    >
    <meta
        property="og:description"
        content="@yield('meta_description', __('site.meta_description'))"
    >
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta
        property="og:site_name"
        content="{{ config('seo.organization.name') }}"
    >
    <meta property="og:locale" content="{{ $seoOgLocale }}">

    @foreach ($seoOgLocaleAlternates as $alternateLocale)
        <meta
            property="og:locale:alternate"
            content="{{ $alternateLocale }}"
        >
    @endforeach

    @if ($seoImage)
        <meta property="og:image" content="{{ $seoImage }}">
    @endif

    <meta
        name="twitter:card"
        content="{{ $seoImage ? 'summary_large_image' : 'summary' }}"
    >
    <meta
        name="twitter:title"
        content="@yield('title', __('site.title'))"
    >
    <meta
        name="twitter:description"
        content="@yield('meta_description', __('site.meta_description'))"
    >

    @if ($seoImage)
        <meta name="twitter:image" content="{{ $seoImage }}">
    @endif

    <meta name="theme-color" content="#ffffff">

    @vite([
        'resources/css/app.css',
        'resources/css/mobile.css',
        'resources/css/auth.css',
        'resources/js/app.js',
        'resources/js/analytics.js'
    ])

    @include('partials.currency-style')

    @foreach ($seoSchemas as $schema)
        <script type="application/ld+json">{!! json_encode(
            $schema,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        ) !!}</script>
    @endforeach

    @stack('head')
</head>
<body
    data-portal-route="{{ request()->route()?->getName() }}"
    data-portal-locale="{{ app()->getLocale() }}"
>
    <div class="site-shell">
        @include('partials.header')

        <main class="site-main">
            @yield('content')
        </main>

        @include('partials.footer')
    </div>

    @stack('scripts')
</body>
</html>
