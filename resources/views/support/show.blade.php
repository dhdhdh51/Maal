@extends('layouts.app')
@section('title', $ticket->subject)
@section('content')
    <div class="max-w-2xl">
        <a href="{{ route('support.index') }}" class="text-sm text-gray-400 hover:text-gray-200">&larr; All tickets</a>
        <div class="flex items-center justify-between mt-2 mb-5">
            <h1 class="text-xl font-bold">{{ $ticket->subject }}</h1>
            <span class="text-xs px-2 py-1 rounded-full bg-white/10">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
        </div>

        <div class="space-y-3 mb-6">
            @foreach ($ticket->replies as $reply)
                <div class="glass rounded-xl p-4 {{ $reply->is_staff ? 'border-l-2 border-violet-500' : '' }}">
                    <p class="text-xs text-gray-500 mb-1">{{ $reply->is_staff ? 'Support' : ($reply->user->name ?? 'You') }} · {{ $reply->created_at->diffForHumans() }}</p>
                    <p class="text-sm whitespace-pre-line">{{ $reply->message }}</p>
                    @if (! empty($reply->attachments))
                        <div class="flex gap-2 mt-2">
                            @foreach ($reply->attachments as $att)
                                <a href="{{ asset('storage/'.$att) }}" target="_blank" class="text-xs text-violet-300 underline">Attachment</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($ticket->status !== 'closed')
            <form method="POST" action="{{ route('support.reply', $ticket) }}" enctype="multipart/form-data" class="glass rounded-xl p-4 space-y-3">
                @csrf
                <textarea name="message" rows="3" placeholder="Write a reply…" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm" required></textarea>
                <div class="flex items-center justify-between">
                    <input type="file" name="attachments[]" multiple class="text-xs text-gray-400">
                    <button class="bg-violet-600 hover:bg-violet-500 px-4 py-2 rounded-lg text-sm font-medium text-white">Send</button>
                </div>
            </form>
        @endif
    </div>
@endsection
