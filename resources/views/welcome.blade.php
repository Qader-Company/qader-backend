<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Qader API</title>
    <link rel="icon" type="image/png" href="{{ asset('images/qader-logo.png') }}">
    <style>
        :root {
            --brand: #0a32db;
            --brand-soft: #f3f6ff;
            --brand-border: #dbe4ff;
            --text: #101828;
            --muted: #5b6475;
            --bg: #f4f6fb;
            --card: #ffffff;
            --border: #e4e9f2;
            --ok: #12b76a;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --brand: #6f8bff;
                --brand-soft: #18203d;
                --brand-border: #2a3566;
                --text: #eef1f7;
                --muted: #a3abbd;
                --bg: #0b1020;
                --card: #121a2e;
                --border: #232c44;
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: var(--bg);
            color: var(--text);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Arabic", Tahoma, sans-serif;
            line-height: 1.5;
        }

        .card {
            width: 100%;
            max-width: 520px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px 32px 28px;
            text-align: center;
            box-shadow: 0 12px 40px rgba(16, 24, 40, .06);
        }

        .logo {
            display: block;
            height: 48px;
            width: auto;
            margin: 0 auto 24px;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -.01em;
        }

        .subtitle { margin: 0; color: var(--muted); font-size: 15px; }
        .subtitle[dir="rtl"] { margin-top: 2px; }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 24px 0;
            padding: 6px 14px;
            border-radius: 999px;
            background: var(--brand-soft);
            border: 1px solid var(--brand-border);
            font-size: 14px;
            font-weight: 600;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--ok);
            box-shadow: 0 0 0 4px rgba(18, 183, 106, .18);
        }

        .meta {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 10px 16px;
            margin: 0 0 24px;
            padding: 16px 18px;
            border: 1px solid var(--border);
            border-radius: 14px;
            text-align: start;
            font-size: 14px;
        }

        .meta dt { color: var(--muted); }
        .meta dd { margin: 0; font-weight: 600; overflow-wrap: anywhere; }
        .meta code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 13px; }

        .links { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 10px;
            border: 1px solid var(--border);
            color: var(--text);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .btn:hover { border-color: var(--brand); color: var(--brand); }
        .btn.primary { background: var(--brand); border-color: var(--brand); color: #fff; }
        .btn.primary:hover { opacity: .9; color: #fff; }

        footer { margin-top: 28px; color: var(--muted); font-size: 12px; }
    </style>
</head>
<body>
    <main class="card">
        <img class="logo" src="{{ asset('images/qader-logo.png') }}" alt="Qader">

        <h1>Qader API</h1>
        <p class="subtitle">Backend services for the Qader hiring platform.</p>
        <p class="subtitle" dir="rtl" lang="ar">الخدمات الخلفية لمنصة قادر للتوظيف.</p>

        <div class="status"><span class="dot"></span> Operational</div>

        <dl class="meta">
            <dt>Base URL</dt>
            <dd><code>{{ url('/api/v1') }}</code></dd>
            <dt>Format</dt>
            <dd>JSON &middot; <code>Accept-Language: en | ar</code></dd>
        </dl>

        <div class="links">
            @if (Route::has('scribe'))
                <a class="btn primary" href="{{ route('scribe') }}">API Documentation</a>
            @endif
            <a class="btn" href="{{ url('/up') }}">Health Check</a>
        </div>

        <footer>&copy; {{ now()->year }} Qader. All rights reserved.</footer>
    </main>
</body>
</html>
