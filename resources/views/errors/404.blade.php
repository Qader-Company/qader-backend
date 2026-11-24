<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>404 | Qader</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('storage/images/qader-favicon.svg') }}">
    <link rel="alternate icon" type="image/png" href="{{ asset('storage/images/qader-favicon.png') }}">
    <style>
        :root {
            color-scheme: light;
            --brand: #386be8;
            --text: #111827;
            --muted: #6b7280;
            --surface: #f7f9ff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: var(--surface);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        main {
            width: min(560px, 100%);
            padding: 48px 32px;
            text-align: center;
            background: #fff;
            border: 1px solid #e6ebf8;
            border-radius: 24px;
            box-shadow: 0 20px 55px rgb(56 107 232 / 10%);
        }

        .logo {
            width: min(260px, 75vw);
            height: auto;
        }

        h1 {
            margin: 28px 0 8px;
            color: var(--brand);
            font-size: clamp(54px, 12vw, 88px);
            line-height: 1;
        }

        p {
            margin: 0;
            color: var(--muted);
            font-size: 18px;
            line-height: 1.7;
        }
    </style>
</head>
<body>
    <main>
        <img class="logo" src="{{ asset('storage/images/qader-logo.png') }}" alt="Qader">
        <h1>404</h1>
        <p>Page not found.</p>
    </main>
</body>
</html>
