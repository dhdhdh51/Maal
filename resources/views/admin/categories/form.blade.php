@extends('admin.layout')
@section('title', $category->exists ? 'Edit category' : 'New category')
@section('heading', $category->exists ? 'Edit: '.$category->name : 'New category')
@section('content')
    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="glass rounded-xl p-5 grid md:grid-cols-2 gap-4 max-w-3xl">
        @csrf @if ($category->exists) @method('PUT') @endif
        <div><label>Name</label><input name="name" value="{{ old('name', $category->name) }}" required></div>
        <div><label>Slug</label><input name="slug" value="{{ old('slug', $category->slug) }}"></div>
        <div class="md:col-span-2"><label>Description</label><textarea name="description" rows="3">{{ old('description', $category->description) }}</textarea></div>
        <div><label>Access type</label>
            <select name="access_type">
                @foreach (['free','paid','subscription','lifetime'] as $t)
                    <option value="{{ $t }}" @selected(old('access_type', $category->access_type) === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
        <div><label>Price</label><input name="price" type="number" step="0.01" value="{{ old('price', $category->price ?? 0) }}"></div>
        <div><label>Currency</label><input name="currency" value="{{ old('currency', $category->currency ?? 'INR') }}" maxlength="3"></div>
        <div><label>Validity (days, blank=lifetime)</label><input name="validity_days" type="number" value="{{ old('validity_days', $category->validity_days) }}"></div>
        <div><label>Preview seconds (blank=global)</label><input name="preview_seconds" type="number" value="{{ old('preview_seconds', $category->preview_seconds) }}"></div>
        <div><label>Sort order</label><input name="sort_order" type="number" value="{{ old('sort_order', $category->sort_order ?? 0) }}"></div>
        <div class="md:col-span-2"><label>SEO title</label><input name="seo_title" value="{{ old('seo_title', $category->seo_title) }}"></div>
        <div class="md:col-span-2"><label>Meta description</label><textarea name="meta_description" rows="2">{{ old('meta_description', $category->meta_description) }}</textarea></div>
        <label class="flex items-center gap-2 text-gray-300"><input type="checkbox" name="is_active" value="1" class="w-auto" @checked(old('is_active', $category->is_active ?? true))> Active</label>
        <label class="flex items-center gap-2 text-gray-300"><input type="checkbox" name="watermark_enabled" value="1" class="w-auto" @checked($category->watermark_enabled)> Force watermark</label>
        <div class="md:col-span-2 flex gap-2"><button class="btn">Save</button>
            @if ($category->exists)<a href="{{ route('admin.categories.index') }}" class="btn btn-ghost">Back</a>@endif
        </div>
    </form>

    @if ($category->exists)
        <div class="glass rounded-xl p-5 mt-6 max-w-3xl">
            <h2 class="font-semibold mb-3">Access plans</h2>
            <table class="mb-4">
                <tr><th>Name</th><th>Type</th><th>Price</th><th>Validity</th><th></th></tr>
                @foreach ($category->plans as $plan)
                    <tr><td>{{ $plan->name }}</td><td>{{ $plan->type }}</td><td>{{ money($plan->price, $plan->currency) }}</td>
                        <td>{{ $plan->validity_days ?? 'Lifetime' }}</td>
                        <td><form method="POST" action="{{ route('admin.categories.plans.destroy', [$category, $plan]) }}">@csrf @method('DELETE')<button class="text-red-300">Delete</button></form></td></tr>
                @endforeach
            </table>
            <form method="POST" action="{{ route('admin.categories.plans.store', $category) }}" class="grid md:grid-cols-4 gap-3 items-end">
                @csrf
                <div><label>Name</label><input name="name" required></div>
                <div><label>Type</label><select name="type">@foreach (['one_time','monthly','quarterly','yearly','lifetime','bundle'] as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</select></div>
                <div><label>Price</label><input name="price" type="number" step="0.01" required></div>
                <div><label>Validity days</label><input name="validity_days" type="number"></div>
                <div><button class="btn">Add plan</button></div>
            </form>
        </div>
    @endif
@endsection
