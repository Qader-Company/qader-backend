<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
</head>
<body>
    <p>{{ $greeting }}</p>

    <p>Your verification code is:</p>

    <p style="font-size: 24px; font-weight: bold; letter-spacing: 4px;">
        {{ $code }}
    </p>

    <p>This code expires in {{ $expiresInMinutes }} minutes.</p>

    <p>If you did not request this code, you can safely ignore this email.</p>
</body>
</html>
