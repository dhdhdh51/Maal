<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Completing payment…</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>body{background:#08080a;color:#e5e7eb;font-family:system-ui,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center}</style>
</head>
<body>
    <p>Opening secure payment…</p>
    <form id="ret" method="POST" action="{{ route('payment.return', ['gateway' => 'razorpay']) }}">
        @csrf
        <input type="hidden" name="reference" value="{{ $payment->reference }}">
        <input type="hidden" name="razorpay_payment_id" id="rp_pid">
        <input type="hidden" name="razorpay_order_id" id="rp_oid">
        <input type="hidden" name="razorpay_signature" id="rp_sig">
    </form>
    <script>
        const opts = @json($order['payload']);
        const rzp = new Razorpay({
            key: opts.key, order_id: opts.order_id, amount: opts.amount, currency: opts.currency,
            name: opts.name, prefill: opts.prefill,
            handler: function (resp) {
                document.getElementById('rp_pid').value = resp.razorpay_payment_id;
                document.getElementById('rp_oid').value = resp.razorpay_order_id;
                document.getElementById('rp_sig').value = resp.razorpay_signature;
                document.getElementById('ret').submit();
            },
        });
        rzp.on('payment.failed', () => window.location = @json(route('payment.failed')));
        rzp.open();
    </script>
</body>
</html>
