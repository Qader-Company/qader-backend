<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Qader</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('storage/images/qader-favicon.svg') }}">
    <link rel="alternate icon" type="image/png" href="{{ asset('storage/images/qader-favicon.png') }}">
    <style>
        :root { color-scheme: light; --bg: #ffffff; --muted: #5b6475; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 24px;
            padding: 24px 16px;
            box-sizing: border-box;
            background: var(--bg);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        .brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
        }

        .brand-logo {
            width: min(420px, 82vw);
            height: auto;
        }

        .brand-tagline {
            margin: 0;
            color: #386be8;
            font-size: clamp(22px, 4vw, 34px);
            font-weight: 700;
            line-height: 1.4;
        }

        footer { color: var(--muted); font-size: 13px; }
    </style>
</head>
<body>
    <div class="brand">
        <img class="brand-logo" src="{{ asset('storage/images/qader-logo.png') }}" alt="Qader">
        <p class="brand-tagline" lang="ar" dir="rtl">كفاءات بلا حدود</p>
    </div>
    <footer>&copy; {{ now()->year }} Qader. All rights reserved.</footer>
</body>
</html>
