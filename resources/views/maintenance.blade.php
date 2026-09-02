<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('maintenance.public.title') }} — Wortal Okulary 3D</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 28px;
            background: #f5f8fb;
            color: #14213d;
        }
        .maintenance-card {
            width: min(680px, 100%);
            padding: 38px;
            border: 1px solid #e2e8f0;
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .10);
        }
        .maintenance-kicker {
            display: inline-block;
            margin-bottom: 10px;
            color: #087da2;
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        h1 {
            margin: 0 0 14px;
            font-size: clamp(2rem, 6vw, 3.4rem);
            line-height: 1.02;
        }
        p {
            margin: 0;
            color: #667085;
            font-size: 1rem;
            line-height: 1.7;
        }
        .maintenance-meta {
            display: grid;
            gap: 10px;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid #edf1f5;
            color: #7b8798;
            font-size: .82rem;
        }
        .maintenance-meta code {
            color: #344054;
        }
        .maintenance-admin {
            display: inline-flex;
            width: fit-content;
            margin-top: 8px;
            color: #087da2;
            font-weight: 700;
            text-decoration: none;
        }
        .maintenance-admin:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <main class="maintenance-card">
        <span class="maintenance-kicker">
            {{ __('maintenance.public.kicker') }}
        </span>
        <h1>{{ __('maintenance.public.title') }}</h1>
        <p>{{ __('maintenance.public.description') }}</p>

        <div class="maintenance-meta">
            <span>{{ __('maintenance.public.retry') }}</span>
            <span>
                {{ __('maintenance.public.current_ip') }}
                <code>{{ $currentIp }}</code>
            </span>
            <a class="maintenance-admin" href="/admin">
                {{ __('maintenance.public.admin') }} →
            </a>
        </div>
    </main>
</body>
</html>
