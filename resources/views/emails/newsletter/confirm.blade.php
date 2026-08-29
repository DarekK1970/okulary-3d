@php
    $confirmUrl = route('newsletter.confirm', [
        'locale' => $subscriber->locale,
        'subscriber' => $subscriber,
        'token' => $token,
    ]);
@endphp
<!doctype html>
<html lang="{{ $subscriber->locale }}">
<body style="margin:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#28344a;">
    <div style="max-width:620px;margin:0 auto;padding:28px 16px;">
        <div style="background:#ffffff;border:1px solid #e1e6eb;border-radius:14px;padding:28px;">
            <div style="font-size:13px;font-weight:700;color:#0d8eb8;letter-spacing:.08em;text-transform:uppercase;">
                Wortal Okulary 3D
            </div>
            <h1 style="font-size:26px;line-height:1.2;margin:12px 0;color:#17233a;">
                {{ __('newsletter.mail.confirm_title') }}
            </h1>
            <p style="font-size:15px;line-height:1.6;color:#657187;">
                {{ __('newsletter.mail.confirm_text') }}
            </p>
            <p style="margin:28px 0;">
                <a href="{{ $confirmUrl }}" style="display:inline-block;background:#17233a;color:#fff;text-decoration:none;padding:13px 20px;border-radius:8px;font-weight:700;">
                    {{ __('newsletter.mail.confirm_button') }}
                </a>
            </p>
            <p style="font-size:12px;line-height:1.6;color:#8c96a5;">
                {{ __('newsletter.mail.ignore_text') }}
            </p>
        </div>
    </div>
</body>
</html>
