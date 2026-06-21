@extends('layouts.app')
@section('title', 'Browse categories')
@section('content')
    <h1 class="text-2xl font-bold mb-5">Categories</h1>
    @if ($categories->isEmpty())
        <p class="text-gray-400">No categories yet.</p>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($categories as $cat)
                <a href="{{ route('category.show', $cat) }}" class="card relative aspect-[16/10] rounded-xl overflow-hidden glass flex flex-col justify-end p-4">
                    @if ($cat->cover_image)<img src="{{ cdn_url($cat->cover_image) }}" class="absolute inset-0 w-full h-full object-cover opacity-60" alt="">@endif
                    <span class="relative font-semibold">{{ $cat->name }}</span>
                    <span class="relative text-xs text-gray-400">
                        {{ $cat->videos_count }} videos ·
                        {{ $cat->access_type === 'free' ? 'Free' : ucfirst($cat->access_type).' · '.money($cat->price, $cat->currency) }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif
@endsection
