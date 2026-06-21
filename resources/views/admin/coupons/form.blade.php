@extends('admin.layout')
@section('title', 'Coupon')
@section('heading', $coupon->exists ? 'Edit coupon' : 'New coupon')
@section('content')
    <form method="POST" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="glass rounded-xl p-5 grid md:grid-cols-2 gap-4 max-w-3xl">
        @csrf @if ($coupon->exists) @method('PUT') @endif
        <div><label>Code</label><input name="code" value="{{ old('code', $coupon->code) }}" required class="uppercase"></div>
        <div><label>Type</label><select name="type">@foreach (['percentage','flat','free_access'] as $t)<option value="{{ $t }}" @selected(old('type', $coupon->type) === $t)>{{ $t }}</option>@endforeach</select></div>
        <div><label>Value</label><input name="value" type="number" step="0.01" value="{{ old('value', $coupon->value ?? 0) }}"></div>
        <div><label>Max discount (cap)</label><input name="max_discount" type="number" step="0.01" value="{{ old('max_discount', $coupon->max_discount) }}"></div>
        <div><label>Min order</label><input name="min_order_amount" type="number" step="0.01" value="{{ old('min_order_amount', $coupon->min_order_amount) }}"></div>
        <div><label>Category (scope)</label><select name="category_id"><option value="">All</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected($coupon->category_id == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
        <div><label>Usage limit</label><input name="usage_limit" type="number" value="{{ old('usage_limit', $coupon->usage_limit) }}"></div>
        <div><label>Per-user limit</label><input name="per_user_limit" type="number" value="{{ old('per_user_limit', $coupon->per_user_limit ?? 1) }}" required></div>
        <div><label>Starts at</label><input name="starts_at" type="datetime-local" value="{{ optional($coupon->starts_at)->format('Y-m-d\TH:i') }}"></div>
        <div><label>Expires at</label><input name="expires_at" type="datetime-local" value="{{ optional($coupon->expires_at)->format('Y-m-d\TH:i') }}"></div>
        <label class="flex items-center gap-2 text-gray-300"><input type="checkbox" name="first_purchase_only" value="1" class="w-auto" @checked($coupon->first_purchase_only)> First purchase only</label>
        <label class="flex items-center gap-2 text-gray-300"><input type="checkbox" name="is_active" value="1" class="w-auto" @checked(old('is_active', $coupon->is_active ?? true))> Active</label>
        <div class="md:col-span-2"><button class="btn">Save</button> <a href="{{ route('admin.coupons.index') }}" class="btn btn-ghost">Back</a></div>
    </form>
@endsection
