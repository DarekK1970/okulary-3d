<!DOCTYPE html>
<html lang="{{ $partner->source_locale ?: config('locales.default', 'pl') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('partners.mail.subject') }}</title>
</head>
<body style="margin:0;padding:28px;background:#f4f7fb;font-family:Arial,sans-serif;color:#17233d;">
    <div style="max-width:640px;margin:0 auto;background:#fff;border:1px solid #e3e8ef;border-radius:16px;padding:30px;">
        <h1 style="margin:0 0 18px;font-size:24px;">{{ __('partners.mail.heading') }}</h1>
        <p style="line-height:1.65;">{{ __('partners.mail.intro', ['name' => $partner->name]) }}</p>
        <p style="line-height:1.65;">{{ __('partners.mail.instruction') }}</p>
        <p style="margin:28px 0;">
            <a href="{{ $verificationUrl }}" style="display:inline-block;padding:13px 20px;border-radius:9px;background:#ff3048;color:#fff;text-decoration:none;font-weight:700;">
                {{ __('partners.mail.button') }}
            </a>
        </p>
        <p style="font-size:13px;line-height:1.6;color:#6f7c90;">{{ __('partners.mail.expires') }}</p>
        <p style="font-size:13px;line-height:1.6;color:#6f7c90;">{{ __('partners.mail.ignore') }}</p>
    </div>
</body>
</html>
