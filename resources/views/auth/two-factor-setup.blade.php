@extends('layouts.auth')
@section('title', 'Two-factor setup')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Two-factor authentication</h1>
    <p class="text-sm text-gray-400 mb-6">Scan the QR in your authenticator app, or enter the key manually.</p>
@endsection
@section('content')
    @if (session('recovery_codes'))
        <div class="mb-4 rounded-lg bg-amber-500/10 border border-amber-500/30 p-4 text-sm">
            <p class="text-amber-300 font-medium mb-2">Save your recovery codes</p>
            <div class="grid grid-cols-2 gap-1 font-mono text-amber-200">
                @foreach (session('recovery_codes') as $code)
                    <span>{{ $code }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="flex justify-center mb-4">
        {{-- QR rendered client-side from the otpauth URI (no external calls). --}}
        <div id="qrcode" class="bg-white p-3 rounded-lg"></div>
    </div>

    <div class="mb-5 text-center">
        <p class="text-xs text-gray-400 mb-1">Manual key</p>
        <code class="text-sm text-violet-200 break-all">{{ $secret }}</code>
    </div>

    <form method="POST" action="{{ route('2fa.enable') }}" class="space-y-4">
        @csrf
        <input name="code" inputmode="numeric" maxlength="6" required placeholder="Enter 6-digit code"
               class="w-full text-center tracking-[0.5em] rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 focus:border-violet-400 outline-none">
        <button class="w-full rounded-lg bg-violet-600 hover:bg-violet-500 transition py-2.5 font-medium text-white">
            Enable 2FA
        </button>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        QRCode.toCanvas(document.createElement('canvas'), @json($otpauthUrl), { width: 180 }, function (err, canvas) {
            if (!err) document.getElementById('qrcode').appendChild(canvas);
        });
    </script>
@endsection
