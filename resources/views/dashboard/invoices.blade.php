@extends('layouts.auth')
@section('title', 'Invoices')
@section('heading')
    <h1 class="text-xl font-semibold mb-1">Payment history</h1>
    <p class="text-sm text-gray-400 mb-6">Your invoices and receipts.</p>
@endsection
@section('content')
    <div class="space-y-2">
        @forelse ($payments as $p)
            <a href="{{ route('invoices.show', $p) }}" class="flex items-center justify-between rounded-lg border border-white/10 bg-white/5 px-4 py-3 hover:bg-white/10">
                <div>
                    <p class="text-sm font-medium text-gray-100">{{ $p->invoice_number ?? $p->reference }}</p>
                    <p class="text-xs text-gray-500">{{ optional($p->paid_at)->format('d M Y') }} · {{ ucfirst($p->gateway) }}</p>
                </div>
                <span class="text-sm text-violet-300">{{ money($p->total, $p->currency) }}</span>
            </a>
        @empty
            <p class="text-sm text-gray-500 text-center py-6">No invoices yet.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $payments->links() }}</div>
@endsection
