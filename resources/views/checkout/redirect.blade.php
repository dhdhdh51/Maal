<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Redirecting to payment…</title>
    <style>body{background:#08080a;color:#e5e7eb;font-family:system-ui,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center}</style>
</head>
<body>
    <div style="text-align:center">
        <p>Redirecting to the secure payment page…</p>
        <form id="gw" method="{{ $order['method'] ?? 'POST' }}" action="{{ $order['url'] }}">
            @foreach (($order['fields'] ?? []) as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <noscript><button type="submit">Continue</button></noscript>
        </form>
    </div>
    <script>document.getElementById('gw').submit();</script>
</body>
</html>
