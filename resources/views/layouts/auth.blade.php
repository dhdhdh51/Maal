<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Account') — {{ setting('site_name', config('app.name')) }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body{background:#08080a;color:#e5e7eb;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
        .glass{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);backdrop-filter:blur(14px)}
        .brand{background:linear-gradient(90deg,#a78bfa,#22d3ee);-webkit-background-clip:text;background-clip:text;color:transparent}
        .glow{box-shadow:0 0 40px -10px rgba(167,139,250,.45)}
        input,select{color-scheme:dark}
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 antialiased">
    <div class="absolute inset-0 overflow-hidden -z-10">
        <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-violet-600/20 blur-3xl"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-cyan-500/20 blur-3xl"></div>
    </div>

    <div class="w-full max-w-md">
        <a href="{{ route('home') }}" class="block text-center mb-6">
            <span class="brand text-3xl font-extrabold tracking-wide">{{ setting('site_name', 'Maal') }}</span>
        </a>

        <div class="glass glow rounded-2xl p-7">
            @yield('heading')

            @if (session('status'))
                <div class="mb-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 px-4 py-3 text-sm text-emerald-300">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-500/10 border border-red-500/30 px-4 py-3 text-sm text-red-300">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>

        <p class="text-center text-xs text-gray-500 mt-6">
            &copy; {{ date('Y') }} {{ setting('site_name', 'Maal') }}. All rights reserved.
        </p>
    </div>
</body>
</html>
