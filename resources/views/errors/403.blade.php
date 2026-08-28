<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Brak dostępu</title>
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f5f7fa;color:#172033;font-family:Arial,sans-serif}
        .box{width:min(90%,560px);padding:42px;border:1px solid #e1e6ed;border-radius:20px;background:#fff;box-shadow:0 18px 50px rgba(16,24,44,.08);text-align:center}
        .code{color:#ff3048;font-size:4rem;font-weight:800}
        h1{margin:8px 0 10px}
        p{color:#667085;line-height:1.6}
        a{display:inline-block;margin-top:18px;padding:11px 18px;border-radius:10px;background:#0aa7df;color:#fff;text-decoration:none;font-weight:700}
    </style>
</head>
<body>
    <main class="box">
        <div class="code">403</div>
        <h1>Brak uprawnień</h1>
        <p>Twoje konto nie posiada uprawnień wymaganych do otwarcia tej części panelu administracyjnego.</p>
        <a href="{{ auth()->check() && auth()->user()->canAccessAdminPanel() ? route('admin.dashboard') : route('home', ['locale' => config('locales.default', 'pl')]) }}">
            Wróć
        </a>
    </main>
</body>
</html>
