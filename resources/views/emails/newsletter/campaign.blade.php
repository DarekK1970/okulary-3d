<!doctype html>
<html lang="{{ $campaign->locale }}">
<body style="margin:0;background:#f4f6f8;font-family:Arial,sans-serif;color:#28344a;">
    @if ($campaign->preheader)
        <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
            {{ $campaign->preheader }}
        </div>
    @endif

    <div style="max-width:680px;margin:0 auto;padding:24px 14px;">
        <div style="background:#ffffff;border:1px solid #e1e6eb;border-radius:14px;overflow:hidden;">
            <div style="padding:20px 26px;border-bottom:1px solid #edf0f3;">
                <strong style="color:#17233a;font-size:18px;">Wortal Okulary 3D</strong>
                @if ($isTest)
                    <span style="margin-left:8px;color:#a8761d;font-size:11px;font-weight:700;">TEST</span>
                @endif
            </div>

            <div style="padding:28px 26px;font-size:15px;line-height:1.65;color:#4e5b70;">
                {!! $campaign->body_html !!}
            </div>

            <div style="padding:18px 26px;background:#f8fafb;border-top:1px solid #edf0f3;font-size:11px;line-height:1.55;color:#8b95a4;">
                <p style="margin:0 0 7px;">{{ __('newsletter.mail.footer_reason') }}</p>
                <a href="{{ $unsubscribeUrl }}" style="color:#607085;">
                    {{ __('newsletter.mail.unsubscribe') }}
                </a>
            </div>
        </div>
    </div>
</body>
</html>
