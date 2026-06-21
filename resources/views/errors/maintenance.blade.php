<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance — {{ setting('site_name', config('app.name')) }}</title>
    <style>
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0a0a0b;color:#e5e7eb;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
        .card{max-width:480px;padding:2.5rem;text-align:center;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:1.25rem;backdrop-filter:blur(12px)}
        h1{font-size:1.5rem;margin:0 0 .75rem}
        p{color:#9ca3af;line-height:1.6}
        .logo{font-weight:800;letter-spacing:.05em;font-size:1.75rem;background:linear-gradient(90deg,#a78bfa,#22d3ee);-webkit-background-clip:text;background-clip:text;color:transparent;margin-bottom:1rem}
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">{{ setting('site_name', 'Maal') }}</div>
        <h1>We'll be right back</h1>
        <p>{{ $message ?? 'The platform is undergoing scheduled maintenance.' }}</p>
    </div>
</body>
</html>
