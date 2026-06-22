@extends('admin.layout')
@section('title', 'Audit logs')
@section('heading', 'Audit logs')
@section('content')
    <form method="GET" class="mb-4"><input name="action" value="{{ request('action') }}" placeholder="Filter by action…" class="max-w-sm"></form>
    <div class="glass rounded-xl p-4">
        <table>
            <tr><th>When</th><th>Actor</th><th>Action</th><th>Description</th><th>IP</th></tr>
            @foreach ($logs as $log)
                <tr><td class="text-xs">{{ $log->created_at->format('d M H:i') }}</td>
                    <td>{{ $log->user?->email ?? 'system' }}</td>
                    <td class="font-mono text-xs">{{ $log->action }}</td>
                    <td class="text-xs text-gray-400">{{ $log->description }}</td>
                    <td class="text-xs text-gray-500">{{ $log->ip_address }}</td></tr>
            @endforeach
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
