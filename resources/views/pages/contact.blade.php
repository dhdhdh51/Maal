@extends('layouts.app')
@section('title', 'Contact us')
@section('content')
    <div class="max-w-xl mx-auto">
        <h1 class="text-3xl font-bold mb-2">Contact us</h1>
        <p class="text-sm text-gray-400 mb-6">Questions, billing or content issues — we're here to help.</p>
        <form method="POST" action="{{ route('contact.submit') }}" class="glass rounded-xl p-5 space-y-4">
            @csrf
            <div><label class="block text-sm mb-1.5 text-gray-300">Name</label>
                <input name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm"></div>
            <div><label class="block text-sm mb-1.5 text-gray-300">Email</label>
                <input name="email" type="email" value="{{ old('email', auth()->user()->email ?? '') }}" required class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm"></div>
            <div><label class="block text-sm mb-1.5 text-gray-300">Message</label>
                <textarea name="message" rows="5" required class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm"></textarea></div>
            <button class="w-full rounded-lg bg-violet-600 hover:bg-violet-500 py-2.5 font-medium text-white">Send message</button>
        </form>
        @if (setting('support_email'))<p class="text-center text-xs text-gray-500 mt-4">Or email {{ setting('support_email') }}</p>@endif
    </div>
@endsection
