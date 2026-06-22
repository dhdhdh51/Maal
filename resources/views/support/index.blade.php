@extends('layouts.app')
@section('title', 'Support')
@section('content')
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-2xl font-bold">Support tickets</h1>
        <a href="{{ route('support.create') }}" class="bg-violet-600 hover:bg-violet-500 px-4 py-2 rounded-lg text-sm font-medium text-white">New ticket</a>
    </div>
    @if ($tickets->isEmpty())
        <p class="text-gray-400 text-center py-16">No tickets yet.</p>
    @else
        <div class="space-y-2">
            @foreach ($tickets as $ticket)
                <a href="{{ route('support.show', $ticket) }}" class="flex items-center justify-between glass rounded-xl px-4 py-3 hover:bg-white/10">
                    <div>
                        <p class="text-sm font-medium">{{ $ticket->subject }}</p>
                        <p class="text-xs text-gray-500">{{ $ticket->reference }} · {{ ucfirst($ticket->category) }}</p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full bg-white/10">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $tickets->links() }}</div>
    @endif
@endsection
