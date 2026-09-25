<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $purpose }} verification code</title>
</head>
<body>
    <h1>Your verification code</h1>
    <p>Use this code to {{ strtolower($purpose) }}:</p>
    <p style="font-size: 28px; font-weight: 700; letter-spacing: 8px;">{{ $code }}</p>
    <p>This code expires in 10 minutes. If you did not request it, you can ignore this email.</p>
</body>
</html>
