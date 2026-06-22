@extends('admin.layout')
@section('title', 'System health')
@section('heading', 'System health')
@section('content')
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach ([
            ['Database', $checks['database'] ? 'OK' : 'DOWN'],
            ['Queue pending', $checks['queue_pending']],
            ['Failed jobs', $checks['failed_jobs']],
            ['Failed transcodes', $failedTranscodes],
            ['Processing now', $processingNow],
            ['Storage used', number_format($storageUsed / (1024**3), 2).' GB'],
            ['Storage quota', $storageMax ? number_format($storageMax / (1024**3), 0).' GB' : 'Unlimited'],
        ] as [$label, $value])
            <div class="glass rounded-xl p-4"><p class="text-xs text-gray-400">{{ $label }}</p><p class="text-xl font-bold mt-1">{{ $value }}</p></div>
        @endforeach
    </div>
    <div class="glass rounded-xl p-4">
        <h2 class="font-semibold mb-3">Recent processing failures</h2>
        <table>
            <tr><th>Video</th><th>Stage</th><th>Error</th><th>When</th></tr>
            @forelse ($recentFailures as $job)
                <tr><td>{{ $job->video?->title ?? '#'.$job->video_id }}</td><td>{{ $job->stage }}</td>
                    <td class="text-xs text-red-300">{{ Str::limit($job->error_message, 80) }}</td>
                    <td class="text-xs">{{ $job->updated_at->diffForHumans() }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-gray-500">No recent failures.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
