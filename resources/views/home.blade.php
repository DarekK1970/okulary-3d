<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('site.title') }}</title>
</head>
<body>
    <h1>{{ __('site.headline') }}</h1>

    <p>{{ __('site.description') }}</p>

    <p>
        {{ __('site.current_language') }}:
        <strong>{{ app()->getLocale() }}</strong>
    </p>

    <nav aria-label="{{ __('site.language_switcher') }}">
        @foreach (config('locales.supported', []) as $locale => $language)
            @if (! $loop->first)
                <span aria-hidden="true"> | </span>
            @endif

            <a href="{{ url('/' . $locale) }}"
               @if (app()->getLocale() === $locale) aria-current="page" @endif>
                {{ $language['native'] }}
            </a>
        @endforeach
    </nav>
</body>
</html>
