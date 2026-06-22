<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unavailable in your region</title>
    <style>
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0a0a0b;color:#e5e7eb;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
        .card{max-width:480px;padding:2.5rem;text-align:center;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:1.25rem}
        h1{font-size:1.5rem;margin:0 0 .75rem}
        p{color:#9ca3af;line-height:1.6}
    </style>
</head>
<body>
    <div class="card">
        <h1>Not available in your region</h1>
        <p>We're sorry, but this service isn't available in your country{{ isset($country) ? ' ('.$country.')' : '' }} at this time.</p>
    </div>
</body>
</html>
