@php
    $isRtl = app()->getLocale() === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6fb;
            color: #101828;
            font-family: Arial, "Helvetica Neue", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        table {
            border-collapse: collapse;
        }

        img {
            border: 0;
            display: block;
        }

        p {
            margin: 0 0 16px;
        }

        .email-shell {
            width: 100%;
            background-color: #f4f6fb;
        }

        .email-container {
            width: 100%;
            max-width: 620px;
        }

        .email-card {
            background-color: #ffffff;
            border: 1px solid #e4e9f2;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
        }

        .email-accent {
            height: 6px;
            background-color: #0a32db;
            font-size: 0;
            line-height: 0;
        }

        .email-content {
            padding: 40px 44px;
            text-align: {{ $isRtl ? 'right' : 'left' }};
        }

        .email-title {
            margin: 0 0 22px;
            color: #101828;
            font-size: 26px;
            line-height: 34px;
            font-weight: 700;
        }

        .email-text {
            color: #475467;
            font-size: 16px;
            line-height: 26px;
        }

        .info-box {
            margin: 24px 0;
            padding: 20px 22px;
            background-color: #f3f6ff;
            border: 1px solid #dbe4ff;
            border-radius: 12px;
        }

        .info-label {
            margin: 0 0 6px;
            color: #667085;
            font-size: 13px;
            line-height: 19px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            margin: 0;
            color: #101828;
            font-size: 16px;
            line-height: 24px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .notice-box {
            margin-top: 22px;
            padding: 14px 16px;
            color: #344054;
            background-color: #f8f9fc;
            border-{{ $isRtl ? 'right' : 'left' }}: 4px solid #0a32db;
            border-radius: 8px;
            font-size: 14px;
            line-height: 22px;
        }

        .email-footer {
            padding: 24px 20px 8px;
            color: #98a2b3;
            font-size: 12px;
            line-height: 18px;
            text-align: center;
        }

        @media only screen and (max-width: 640px) {
            .email-frame {
                padding: 20px 12px !important;
            }

            .email-content {
                padding: 30px 24px !important;
            }

            .email-title {
                font-size: 23px !important;
                line-height: 31px !important;
            }
        }
    </style>
</head>
<body>
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        @yield('preheader')
    </div>

    <table role="presentation" class="email-shell" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="email-frame" align="center" style="padding: 36px 16px;">
                <table role="presentation" class="email-container" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center" style="padding: 0 0 24px;">
                            <img
                                src="{{ $message->embed(public_path('images/qader-logo.png')) }}"
                                width="156"
                                alt="{{ config('app.name') }}"
                                style="width:156px;max-width:100%;height:auto;"
                            >
                        </td>
                    </tr>
                    <tr>
                        <td class="email-card">
                            <div class="email-accent">&nbsp;</div>
                            <div class="email-content">
                                @yield('content')
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-footer">
                            &copy; {{ now()->year }} {{ config('app.name') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
