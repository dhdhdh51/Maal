@extends('layouts.auth')
@section('title', 'Age verification')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Age verification</h1>
    <p class="text-sm text-gray-400 mb-6">You must be at least {{ $minAge }} years old to enter.</p>
@endsection
@section('content')
    <form method="POST" action="{{ route('age-gate.confirm') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="redirect" value="{{ $redirect }}">
        <label class="flex items-start gap-2 text-sm text-gray-300">
            <input type="checkbox" name="confirm" value="1" required class="mt-0.5 rounded bg-white/5 border-white/20 text-violet-500">
            I confirm that I am {{ $minAge }} years of age or older and I consent to viewing this content.
        </label>
        <div class="grid grid-cols-2 gap-3">
            <a href="https://www.google.com" class="rounded-lg border border-white/10 py-2.5 text-center text-sm text-gray-300 hover:bg-white/5">Leave</a>
            <button class="rounded-lg bg-violet-600 hover:bg-violet-500 transition py-2.5 font-medium text-white">Enter</button>
        </div>
    </form>
@endsection
