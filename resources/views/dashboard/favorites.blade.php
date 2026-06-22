@extends('layouts.app')
@section('title', 'Favorites')
@section('content')
    <h1 class="text-2xl font-bold mb-5">Your favorites</h1>
    @if ($videos->isEmpty())
        <p class="text-gray-400 text-center py-16">No favorites yet.</p>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            @foreach ($videos as $video)
                @include('partials.video-card', ['video' => $video])
            @endforeach
        </div>
    @endif
@endsection
