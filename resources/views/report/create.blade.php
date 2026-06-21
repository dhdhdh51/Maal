@extends('layouts.app')
@section('title', 'Report content')
@section('content')
    <div class="max-w-lg mx-auto">
        <h1 class="text-2xl font-bold mb-2">Report content</h1>
        <p class="text-sm text-gray-400 mb-5">Help us keep the platform lawful and safe.</p>

        <form method="POST" action="{{ route('report.store') }}" class="glass rounded-xl p-5 space-y-4">
            @csrf
            <input type="hidden" name="type" value="{{ $video ? 'video' : 'category' }}">
            <input type="hidden" name="id" value="{{ $video->id ?? $category->id ?? '' }}">

            @if ($video)<p class="text-sm text-gray-300">Reporting video: <strong>{{ $video->title }}</strong></p>@endif
            @if ($category)<p class="text-sm text-gray-300">Reporting category: <strong>{{ $category->name }}</strong></p>@endif

            <div>
                <label class="block text-sm mb-1.5 text-gray-300">Reason</label>
                <select name="reason" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm">
                    @foreach ($reasons as $r)<option value="{{ $r }}">{{ ucfirst(str_replace('_', ' ', $r)) }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1.5 text-gray-300">Details (optional)</label>
                <textarea name="details" rows="4" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm"></textarea>
            </div>
            @guest
                <input name="reporter_email" type="email" placeholder="Your email (optional)" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm">
            @endguest
            <button class="bg-violet-600 hover:bg-violet-500 px-4 py-2.5 rounded-lg text-sm font-medium text-white">Submit report</button>
        </form>
    </div>
@endsection
