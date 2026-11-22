<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Qader</title>
    <link rel="icon" type="image/png" href="{{ asset('storage/images/qader-logo.png') }}">
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

        img { height: 64px; width: auto; }

        footer { color: var(--muted); font-size: 13px; }
    </style>
</head>
<body>
    <img src="{{ asset('storage/images/qader-logo.png') }}" alt="Qader">
    <footer>&copy; {{ now()->year }} Qader. All rights reserved.</footer>
</body>
</html>
