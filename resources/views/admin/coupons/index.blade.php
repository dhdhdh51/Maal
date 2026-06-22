@extends('admin.layout')
@section('title', 'Coupons')
@section('heading', 'Coupons')
@section('content')
    <div class="flex justify-end mb-4"><a href="{{ route('admin.coupons.create') }}" class="btn">New coupon</a></div>
    <div class="glass rounded-xl p-4">
        <table>
            <tr><th>Code</th><th>Type</th><th>Value</th><th>Used</th><th>Active</th><th></th></tr>
            @foreach ($coupons as $c)
                <tr><td class="font-mono">{{ $c->code }}</td><td>{{ $c->type }}</td><td>{{ $c->value }}</td>
                    <td>{{ $c->used_count }}{{ $c->usage_limit ? '/'.$c->usage_limit : '' }}</td>
                    <td>{!! $c->is_active ? '✓' : '—' !!}</td>
                    <td><a href="{{ route('admin.coupons.edit', $c) }}" class="text-violet-300">Edit</a></td></tr>
            @endforeach
        </table>
    </div>
    <div class="mt-4">{{ $coupons->links() }}</div>
@endsection
