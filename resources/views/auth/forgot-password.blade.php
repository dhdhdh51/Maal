@extends('layouts.auth')
@section('title', 'Forgot password')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Reset your password</h1>
    <p class="text-sm text-gray-400 mb-6">We'll email you a 6-digit reset code.</p>
@endsection
@section('content')
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm mb-1.5 text-gray-300">Email</label>
            <input name="email" type="email" value="{{ old('email') }}" required autofocus
                   class="w-full rounded-lg bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm focus:border-violet-400 outline-none">
        </div>
        <button class="w-full rounded-lg bg-violet-600 hover:bg-violet-500 transition py-2.5 font-medium text-white">
            Send reset code
        </button>
    </form>
    <p class="text-center text-sm text-gray-400 mt-6">
        <a href="{{ route('login') }}" class="text-violet-300 hover:text-violet-200">Back to sign in</a>
    </p>
@endsection
