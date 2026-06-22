<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ setting('site_name', 'Maal') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        body{background:#08080a;color:#e5e7eb;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif}
        .glass{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08)}
        .brand{background:linear-gradient(90deg,#a78bfa,#22d3ee);-webkit-background-clip:text;background-clip:text;color:transparent}
        .navlink{display:block;padding:.5rem .75rem;border-radius:.5rem;font-size:.875rem;color:#cbd5e1}
        .navlink:hover{background:rgba(255,255,255,.06)}
        .navlink.active{background:rgba(124,58,237,.2);color:#fff}
        [x-cloak]{display:none!important}
        table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:.6rem .5rem;font-size:.875rem;border-bottom:1px solid rgba(255,255,255,.06)}
        .btn{display:inline-block;background:#7c3aed;padding:.5rem .9rem;border-radius:.5rem;font-size:.875rem;color:#fff}
        .btn:hover{background:#6d28d9}.btn-ghost{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1)}
        input,select,textarea{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:.5rem;padding:.5rem .7rem;font-size:.875rem;width:100%;color:#e5e7eb}
        label{font-size:.8rem;color:#9ca3af;display:block;margin-bottom:.3rem}
    </style>
</head>
<body class="min-h-screen">
<div class="flex">
    <aside x-data="{open:false}" class="hidden lg:block w-60 shrink-0 h-screen sticky top-0 glass border-r border-white/10 p-3 overflow-y-auto">
        <a href="{{ route('admin.dashboard') }}" class="brand text-xl font-extrabold px-2 block mb-4">{{ setting('site_name', 'Maal') }} Admin</a>
        @php($r = fn ($p) => request()->routeIs($p) ? 'active' : '')
        <a href="{{ route('admin.dashboard') }}" class="navlink {{ $r('admin.dashboard') }}">Dashboard</a>
        @can('categories.view')<a href="{{ route('admin.categories.index') }}" class="navlink {{ $r('admin.categories.*') }}">Categories</a>@endcan
        @can('videos.view')<a href="{{ route('admin.videos.index') }}" class="navlink {{ $r('admin.videos.*') }}">Videos</a>@endcan
        @can('videos.upload')<a href="{{ route('admin.uploads.index') }}" class="navlink {{ $r('admin.uploads.*') }}">Upload</a>@endcan
        @can('videos.update')<a href="{{ route('admin.preview.index') }}" class="navlink {{ $r('admin.preview.*') }}">Preview durations</a>@endcan
        @can('payments.view')<a href="{{ route('admin.payments.index') }}" class="navlink {{ $r('admin.payments.*') }}">Payments</a>@endcan
        @can('coupons.manage')<a href="{{ route('admin.coupons.index') }}" class="navlink {{ $r('admin.coupons.*') }}">Coupons</a>@endcan
        @can('users.view')<a href="{{ route('admin.users.index') }}" class="navlink {{ $r('admin.users.*') }}">Users</a>@endcan
        @can('reports.view')<a href="{{ route('admin.reports.index') }}" class="navlink {{ $r('admin.reports.*') }}">Reports</a>@endcan
        @can('tickets.view')<a href="{{ route('admin.tickets.index') }}" class="navlink {{ $r('admin.tickets.*') }}">Support</a>@endcan
        @can('homepage.manage')<a href="{{ route('admin.homepage.index') }}" class="navlink {{ $r('admin.homepage.*') }}">Homepage</a>@endcan
        @can('banners.manage')<a href="{{ route('admin.banners.index') }}" class="navlink {{ $r('admin.banners.*') }}">Banners</a>@endcan
        @can('emails.manage')<a href="{{ route('admin.email-templates.index') }}" class="navlink {{ $r('admin.email-templates.*') }}">Email templates</a>@endcan
        @can('countries.manage')<a href="{{ route('admin.countries.index') }}" class="navlink {{ $r('admin.countries.*') }}">Countries</a>@endcan
        @can('settings.manage')<a href="{{ route('admin.settings.edit') }}" class="navlink {{ $r('admin.settings.*') }}">Settings</a>@endcan
        @can('roles.manage')<a href="{{ route('admin.roles.index') }}" class="navlink {{ $r('admin.roles.*') }}">Roles</a>@endcan
        @can('audit.view')<a href="{{ route('admin.audit.index') }}" class="navlink {{ $r('admin.audit.*') }}">Audit logs</a>@endcan
        @can('system.health.view')<a href="{{ route('admin.health') }}" class="navlink {{ $r('admin.health') }}">System health</a>@endcan
        <div class="mt-4 pt-4 border-t border-white/10">
            <a href="{{ route('home') }}" class="navlink">← Back to site</a>
            <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="navlink w-full text-left">Sign out</button></form>
        </div>
    </aside>

    <div class="flex-1 min-w-0">
        <header class="lg:hidden glass border-b border-white/10 px-4 py-3 flex items-center justify-between">
            <span class="brand font-extrabold">{{ setting('site_name', 'Maal') }} Admin</span>
            <a href="{{ route('home') }}" class="text-sm text-gray-400">Site</a>
        </header>

        @if (session('status'))
            <div class="mx-4 lg:mx-6 mt-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 px-4 py-3 text-sm text-emerald-300">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mx-4 lg:mx-6 mt-4 rounded-lg bg-red-500/10 border border-red-500/30 px-4 py-3 text-sm text-red-300">
                <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <main class="p-4 lg:p-6">
            <h1 class="text-2xl font-bold mb-5">@yield('heading', 'Admin')</h1>
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
