@extends('layouts.auth')
@section('title', 'Sign in')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Welcome back</h1>
    <p class="text-sm text-gray-400 mb-6">Sign in to continue watching.</p>
@endsection
@section('content')
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm mb-1.5 text-gray-300">Email</label>
            <input name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm focus:border-violet-400 focus:ring-1 focus:ring-violet-400 outline-none">
        </div>
        <div>
            <div class="flex justify-between items-center mb-1.5">
                <label class="text-sm text-gray-300">Password</label>
                <a href="{{ route('password.request') }}" class="text-xs text-violet-300 hover:text-violet-200">Forgot?</a>
            </div>
            <input name="password" type="password" required
                   class="w-full rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm focus:border-violet-400 focus:ring-1 focus:ring-violet-400 outline-none">
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-400">
            <input type="checkbox" name="remember" value="1" class="rounded bg-white/5 border-white/20 text-violet-500">
            Remember me
        </label>
        <button class="w-full rounded-lg bg-violet-600 hover:bg-violet-500 transition py-2.5 font-medium text-white">
            Sign in
        </button>
    </form>
    <p class="text-center text-sm text-gray-400 mt-6">
        New here? <a href="{{ route('register') }}" class="text-violet-300 hover:text-violet-200 font-medium">Create an account</a>
    </p>
@endsection
