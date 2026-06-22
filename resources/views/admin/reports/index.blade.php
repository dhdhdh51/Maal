@extends('admin.layout')
@section('title', 'Reports')
@section('heading', 'Content reports')
@section('content')
    <div class="space-y-3">
        @forelse ($reports as $report)
            <div class="glass rounded-xl p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs px-2 py-0.5 rounded bg-white/10">{{ ucfirst($report->priority) }}</span>
                        <span class="text-sm font-medium ml-2">{{ ucfirst(str_replace('_', ' ', $report->reason)) }}</span>
                        <span class="text-xs text-gray-500 ml-2">{{ class_basename($report->reportable_type) }} #{{ $report->reportable_id }}</span>
                    </div>
                    <span class="text-xs text-gray-500">{{ $report->created_at->diffForHumans() }}</span>
                </div>
                @if ($report->details)<p class="text-sm text-gray-400 mt-2">{{ $report->details }}</p>@endif
                <div class="flex items-center gap-3 mt-3">
                    @if ($report->reportable_type === \App\Models\Video::class && $report->reportable)
                        <form method="POST" action="{{ route('admin.videos.disable', $report->reportable) }}">@csrf<button class="text-xs text-red-300">Disable video</button></form>
                    @endif
                    <form method="POST" action="{{ route('admin.reports.update', $report) }}" class="flex items-center gap-2 ml-auto">
                        @csrf @method('PUT')
                        <select name="status" class="max-w-[150px] text-xs">
                            @foreach (['open','reviewing','resolved','dismissed'] as $s)<option value="{{ $s }}" @selected($report->status === $s)>{{ ucfirst($s) }}</option>@endforeach
                        </select>
                        <input name="reviewer_notes" placeholder="Notes" class="max-w-[200px] text-xs">
                        <button class="btn-ghost btn text-xs">Update</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-gray-400 text-center py-16">No reports.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $reports->links() }}</div>
@endsection
