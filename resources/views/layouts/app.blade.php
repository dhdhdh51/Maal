<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', setting('site_name', 'Maal'))</title>
    <meta name="description" content="@yield('meta_description', setting('meta_description', ''))">
    @stack('head')
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

        <footer class="border-t border-white/10 mt-10 px-4 md:px-6 py-8 text-sm text-gray-400">
            <div class="grid md:grid-cols-4 gap-6 max-w-7xl">
                <div>
                    <span class="brand text-xl font-extrabold">{{ setting('site_name', 'Maal') }}</span>
                    <p class="text-xs text-gray-500 mt-2">{{ setting('site_tagline', 'Premium streaming, unlocked.') }}</p>
                </div>
                <div>
                    <p class="text-gray-200 font-medium mb-2">Explore</p>
                    <a href="{{ route('categories') }}" class="block hover:text-white">Categories</a>
                    <a href="{{ route('search') }}" class="block hover:text-white">Search</a>
                </div>
                <div>
                    <p class="text-gray-200 font-medium mb-2">Legal</p>
                    <a href="{{ route('page', 'terms') }}" class="block hover:text-white">Terms</a>
                    <a href="{{ route('page', 'privacy') }}" class="block hover:text-white">Privacy</a>
                    <a href="{{ route('page', 'content-policy') }}" class="block hover:text-white">Content Policy</a>
                    <a href="{{ route('page', 'refund-policy') }}" class="block hover:text-white">Refunds</a>
                    <a href="{{ route('page', 'dmca') }}" class="block hover:text-white">Copyright / DMCA</a>
                </div>
                <div x-data="{email:'',done:false}">
                    <p class="text-gray-200 font-medium mb-2">Newsletter</p>
                    <div class="flex gap-2">
                        <input type="email" x-model="email" placeholder="you@email.com" class="flex-1 rounded-lg bg-white/5 border border-white/10 px-3 py-2 text-xs">
                        <button @click="fetch('{{ route('newsletter.subscribe') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:JSON.stringify({email,source:'footer'})}).then(()=>done=true)"
                                class="bg-violet-600 hover:bg-violet-500 rounded-lg px-3 text-xs text-white">Join</button>
                    </div>
                    <p x-show="done" x-cloak class="text-xs text-emerald-400 mt-1">Subscribed!</p>
                    <a href="{{ route('contact') }}" class="block mt-3 hover:text-white">Contact us</a>
                </div>
            </div>
            <p class="text-xs text-gray-600 mt-6">&copy; {{ date('Y') }} {{ setting('site_name', 'Maal') }}. All rights reserved.</p>
        </footer>
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

{{-- Success toast --}}
@if (session('status'))
    <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,4000)" x-cloak
         class="fixed bottom-20 md:bottom-6 right-4 z-50 glass rounded-xl px-4 py-3 text-sm text-emerald-300 border border-emerald-500/30 shadow-lg">
        {{ session('status') }}
    </div>
@endif

{{-- Floating WhatsApp support button --}}
@if (setting('whatsapp_enabled', false) && setting('whatsapp_number') && (! setting('whatsapp_on_checkout_only', false) || request()->is('unlock/*', 'checkout*', 'payment/*')))
    <a href="https://wa.me/{{ preg_replace('/\D/', '', setting('whatsapp_number')) }}" target="_blank" rel="noopener"
       class="fixed bottom-20 md:bottom-6 left-4 z-40 w-12 h-12 rounded-full bg-green-500 hover:bg-green-400 flex items-center justify-center shadow-lg" aria-label="WhatsApp support">
        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.82 11.82 0 0 1 8.413 3.488 11.82 11.82 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zM6.597 20.13c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 0 0 1.51 5.26l-.999 3.648 3.978-.708z"/></svg>
    </a>
@endif

{{-- Newsletter popup --}}
@if (setting('newsletter_popup_enabled', false) && ! auth()->check())
    <div x-data="{open:false,email:'',done:false}"
         x-init="if(!localStorage.getItem('nl_seen')){setTimeout(()=>open=true,{{ (int) setting('newsletter_popup_delay', 15) }}*1000)}"
         x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
        <div @click.outside="open=false;localStorage.setItem('nl_seen','1')" class="glass rounded-2xl p-6 max-w-sm w-full text-center">
            <h3 class="text-lg font-semibold mb-1">Stay in the loop</h3>
            <p class="text-sm text-gray-400 mb-4">Get new releases and offers in your inbox.</p>
            <template x-if="!done">
                <div class="space-y-3">
                    <input type="email" x-model="email" placeholder="you@email.com" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm">
                    <button @click="fetch('{{ route('newsletter.subscribe') }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:JSON.stringify({email,source:'popup'})}).then(()=>{done=true;localStorage.setItem('nl_seen','1')})"
                            class="w-full rounded-lg bg-violet-600 hover:bg-violet-500 py-2.5 text-sm font-medium text-white">Subscribe</button>
                    <button @click="open=false;localStorage.setItem('nl_seen','1')" class="text-xs text-gray-500">No thanks</button>
                </div>
            </template>
            <p x-show="done" x-cloak class="text-emerald-400 text-sm">Thanks for subscribing!</p>
        </div>
    </div>
@endif

@stack('scripts')
</body>
</html>
