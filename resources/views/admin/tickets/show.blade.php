@extends('admin.layout')
@section('title', $ticket->subject)
@section('heading', $ticket->subject)
@section('content')
    <div class="max-w-2xl">
        <p class="text-sm text-gray-400 mb-4">{{ $ticket->reference }} · {{ $ticket->user?->email }} · {{ ucfirst($ticket->category) }}</p>
        <div class="space-y-3 mb-6">
            @foreach ($ticket->replies as $reply)
                <div class="glass rounded-xl p-4 {{ $reply->is_staff ? 'border-l-2 border-violet-500' : '' }}">
                    <p class="text-xs text-gray-500 mb-1">{{ $reply->is_staff ? 'Staff' : ($reply->user->name ?? 'User') }} · {{ $reply->created_at->diffForHumans() }}</p>
                    <p class="text-sm whitespace-pre-line">{{ $reply->message }}</p>
                </div>
            @endforeach
        </div>
        <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" class="glass rounded-xl p-4 space-y-3">
            @csrf
            <textarea name="message" rows="3" placeholder="Reply…" required></textarea>
            <div class="flex items-center justify-between">
                <select name="status" class="max-w-[200px]">@foreach (['open','in_progress','waiting_user','resolved','closed'] as $s)<option value="{{ $s }}" @selected($ticket->status === $s)>{{ str_replace('_', ' ', $s) }}</option>@endforeach</select>
                <button class="btn">Send reply</button>
            </div>
        </form>
    </div>
@endsection
