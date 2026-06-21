@extends('layouts.app')
@section('title', $title)
@section('content')
    <div class="max-w-3xl mx-auto">
        <h1 class="text-3xl font-bold mb-2">{{ $title }}</h1>
        @if ($updatedAt)<p class="text-xs text-gray-500 mb-6">Last updated {{ \Illuminate\Support\Carbon::parse($updatedAt)->format('d M Y') }}</p>@endif
        <div class="prose prose-invert max-w-none text-gray-300 leading-relaxed space-y-4">
            {!! $body !!}
        </div>
    </div>
@endsection
