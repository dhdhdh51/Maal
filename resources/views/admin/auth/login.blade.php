@extends('layouts.auth')
@section('title', 'Admin sign in')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Admin Console</h1>
    <p class="text-sm text-gray-400 mb-6">Restricted access. Authorized staff only.</p>
@endsection
@section('content')
    <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm mb-1.5 text-gray-300">Email</label>
            <input name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm focus:border-violet-400 outline-none">
        </div>
        <div>
            <label class="block text-sm mb-1.5 text-gray-300">Password</label>
            <input name="password" type="password" required
                   class="w-full rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm focus:border-violet-400 outline-none">
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-400">
            <input type="checkbox" name="remember" value="1" class="rounded bg-white/5 border-white/20 text-violet-500"> Remember me
        </label>
        <button class="w-full rounded-lg bg-violet-600 hover:bg-violet-500 transition py-2.5 font-medium text-white">
            Sign in
        </button>
    </form>
@endsection
