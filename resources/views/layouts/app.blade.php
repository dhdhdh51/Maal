<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', setting('site_name', 'Maal'))</title>
    <meta name="description" content="@yield('meta_description', setting('meta_description', ''))">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        body{background:#08080a;color:#e5e7eb;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
        .glass{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)}
        .brand{background:linear-gradient(90deg,#a78bfa,#22d3ee);-webkit-background-clip:text;background-clip:text;color:transparent}
        .card{transition:transform .2s ease, box-shadow .2s ease}
        @media (hover:hover){.card:hover{transform:translateY(-4px);box-shadow:0 12px 40px -12px rgba(167,139,250,.45)}}
        ::-webkit-scrollbar{height:8px;width:8px}::-webkit-scrollbar-thumb{background:#27272a;border-radius:4px}
    </style>
</head>
<body class="min-h-screen pb-16 md:pb-0">
<div class="flex">
    {{-- Desktop sidebar --}}
    <aside class="hidden md:flex flex-col w-60 shrink-0 h-screen sticky top-0 glass border-r border-white/10 p-4 gap-1">
        <a href="{{ route('home') }}" class="brand text-2xl font-extrabold tracking-wide px-2 mb-4">{{ setting('site_name', 'Maal') }}</a>
        @php($nav = [['home','Home','/'],['categories','Browse','/categories'],['search','Search','/search']])
        @foreach ($nav as [$key,$label,$url])
            <a href="{{ url($url) }}" class="px-3 py-2 rounded-lg text-sm hover:bg-white/5 {{ request()->is(trim($url,'/') ?: '/') ? 'bg-white/10 text-white' : 'text-gray-300' }}">{{ $label }}</a>
        @endforeach
        @auth
            <div class="mt-4 pt-4 border-t border-white/10 flex flex-col gap-1">
                <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg text-sm text-gray-300 hover:bg-white/5">Dashboard</a>
                <a href="{{ route('favorites.index') }}" class="px-3 py-2 rounded-lg text-sm text-gray-300 hover:bg-white/5">Favorites</a>
                <a href="{{ route('history.index') }}" class="px-3 py-2 rounded-lg text-sm text-gray-300 hover:bg-white/5">Watch history</a>
                <a href="{{ route('access.index') }}" class="px-3 py-2 rounded-lg text-sm text-gray-300 hover:bg-white/5">My access</a>
            </div>
        @endauth
    </aside>

    <div class="flex-1 min-w-0">
        {{-- Top bar --}}
        <header class="sticky top-0 z-30 glass border-b border-white/10 px-4 py-3 flex items-center gap-3">
            <a href="{{ route('home') }}" class="md:hidden brand text-xl font-extrabold">{{ setting('site_name', 'Maal') }}</a>
            <form action="{{ route('search') }}" method="GET" class="flex-1 max-w-lg hidden sm:block">
                <input name="q" value="{{ request('q') }}" placeholder="Search videos, categories…"
                       class="w-full rounded-lg bg-white/5 border border-white/10 px-3.5 py-2 text-sm focus:border-violet-400 outline-none">
            </form>
            <div class="ml-auto flex items-center gap-2">
                @auth
                    <a href="{{ route('notifications.index') }}" class="relative p-2 rounded-lg hover:bg-white/5" title="Notifications">
                        <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.8 23.8 0 0 0 5.454-1.31A8.97 8.97 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.97 8.97 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24 24 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                        @php($unread = auth()->user()->notifications()->whereNull('read_at')->count())
                        @if ($unread)<span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-rose-500"></span>@endif
                    </a>
                    <div x-data="{open:false}" class="relative">
                        <button @click="open=!open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-white/5">
                            <span class="w-8 h-8 rounded-full bg-violet-600 flex items-center justify-center text-sm font-semibold">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span>
                        </button>
                        <div x-show="open" @click.outside="open=false" x-cloak class="absolute right-0 mt-2 w-48 glass rounded-lg p-1 z-40">
                            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-md text-sm hover:bg-white/10">Dashboard</a>
                            <a href="{{ route('devices.index') }}" class="block px-3 py-2 rounded-md text-sm hover:bg-white/10">Devices</a>
                            @can('admin.access')<a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-md text-sm hover:bg-white/10">Admin</a>@endcan
                            <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full text-left px-3 py-2 rounded-md text-sm hover:bg-white/10">Sign out</button></form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/5">Sign in</a>
                    <a href="{{ route('register') }}" class="text-sm px-3 py-2 rounded-lg bg-violet-600 hover:bg-violet-500 text-white">Register</a>
                @endauth
            </div>
        </header>

        @if (session('status'))
            <div class="mx-4 mt-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 px-4 py-3 text-sm text-emerald-300">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mx-4 mt-4 rounded-lg bg-red-500/10 border border-red-500/30 px-4 py-3 text-sm text-red-300">
                <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <main class="p-4 md:p-6 max-w-7xl">@yield('content')</main>
    </div>
</div>

{{-- Mobile bottom nav --}}
<nav class="md:hidden fixed bottom-0 inset-x-0 z-30 glass border-t border-white/10 flex justify-around py-2 text-xs">
    <a href="{{ route('home') }}" class="flex flex-col items-center gap-0.5 px-3 py-1 {{ request()->is('/') ? 'text-violet-300' : 'text-gray-400' }}">Home</a>
    <a href="{{ route('categories') }}" class="flex flex-col items-center gap-0.5 px-3 py-1 {{ request()->is('categories*') ? 'text-violet-300' : 'text-gray-400' }}">Browse</a>
    <a href="{{ route('search') }}" class="flex flex-col items-center gap-0.5 px-3 py-1 {{ request()->is('search*') ? 'text-violet-300' : 'text-gray-400' }}">Search</a>
    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="flex flex-col items-center gap-0.5 px-3 py-1 text-gray-400">Account</a>
</nav>

<style>[x-cloak]{display:none!important}</style>
@stack('scripts')
</body>
</html>
