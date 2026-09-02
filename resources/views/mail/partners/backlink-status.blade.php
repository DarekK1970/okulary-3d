<!doctype html>
<html lang="{{ $partner->source_locale ?: config('locales.default', 'pl') }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('partners.monitor.mail.subject.' . $event) }}</title>
</head>
<body style="font-family:Arial,sans-serif;line-height:1.6;color:#1f2937">
    <h1>{{ __('partners.monitor.mail.heading.' . $event) }}</h1>

    <p>{{ __('partners.monitor.mail.intro', ['name' => $partner->name]) }}</p>
    <p>{{ __('partners.monitor.mail.body.' . $event) }}</p>

    <p>
        <strong>{{ __('partners.form.backlink_url') }}:</strong><br>
        <a href="{{ $partner->backlink_url ?: $partner->website_url }}">{{ $partner->backlink_url ?: $partner->website_url }}</a>
    </p>

    @if ($event !== 'restored')
        <p>{{ __('partners.monitor.mail.recheck') }}</p>
    @endif

    <p>{{ __('partners.monitor.mail.signature') }}</p>
</body>
</html>
