@extends('admin.layout')
@section('title', 'Payments')
@section('heading', 'Payments')
@section('content')
    <div class="glass rounded-xl p-4">
        <table>
            <tr><th>Reference</th><th>User</th><th>Gateway</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr>
            @foreach ($payments as $p)
                <tr>
                    <td class="font-mono text-xs">{{ $p->reference }}</td>
                    <td>{{ $p->user?->email }}</td>
                    <td>{{ ucfirst($p->gateway) }}</td>
                    <td>{{ money($p->total, $p->currency) }}</td>
                    <td><span class="text-xs px-2 py-0.5 rounded bg-white/10">{{ $p->status }}</span></td>
                    <td>{{ $p->created_at->format('d M') }}</td>
                    <td class="flex gap-2">
                        @if ($p->gateway === 'manual' && $p->status === 'pending')
                            <form method="POST" action="{{ route('admin.payments.approve', $p) }}">@csrf<button class="text-xs text-emerald-300">Approve</button></form>
                        @endif
                        @if ($p->status === 'paid')
                            <form method="POST" action="{{ route('admin.payments.refund', $p) }}" onsubmit="return confirm('Refund?')">@csrf<button class="text-xs text-red-300">Refund</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
    <div class="mt-4">{{ $payments->links() }}</div>
@endsection
