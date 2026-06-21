@extends('layouts.app')
@section('title', 'New ticket')
@section('content')
    <div class="max-w-xl">
        <h1 class="text-2xl font-bold mb-5">Create a support ticket</h1>

        @if (! empty($faqs))
            <div class="glass rounded-xl p-4 mb-5">
                <p class="text-sm font-semibold mb-2">Before you ask — FAQs</p>
                <ul class="text-sm text-gray-400 list-disc list-inside space-y-1">
                    @foreach ($faqs as $faq)<li>{{ is_array($faq) ? ($faq['q'] ?? '') : $faq }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('support.store') }}" enctype="multipart/form-data" class="glass rounded-xl p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm mb-1.5 text-gray-300">Category</label>
                <select name="category" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm">
                    @foreach ($categories as $c)<option value="{{ $c }}">{{ ucfirst($c) }} issue</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1.5 text-gray-300">Subject</label>
                <input name="subject" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm" required>
            </div>
            <div>
                <label class="block text-sm mb-1.5 text-gray-300">Message</label>
                <textarea name="message" rows="5" class="w-full rounded-lg bg-white/5 border border-white/10 px-3 py-2.5 text-sm" required></textarea>
            </div>
            <div>
                <label class="block text-sm mb-1.5 text-gray-300">Attachments (screenshots)</label>
                <input type="file" name="attachments[]" multiple accept="image/*,application/pdf" class="text-sm text-gray-400">
            </div>
            <button class="bg-violet-600 hover:bg-violet-500 px-4 py-2.5 rounded-lg text-sm font-medium text-white">Submit ticket</button>
        </form>
    </div>
@endsection
