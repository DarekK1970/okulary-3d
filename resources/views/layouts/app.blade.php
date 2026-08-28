<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', __('site.title'))</title>
    <meta name="description" content="@yield('meta_description', __('site.meta_description'))">

    <meta name="theme-color" content="#ffffff">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body>
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
