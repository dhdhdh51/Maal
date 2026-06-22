@extends('admin.layout')
@section('title', 'Country restrictions')
@section('heading', 'Country restrictions')
@section('content')
    <p class="text-sm text-gray-400 mb-4">If any "allow" rules exist, only those countries can access. Otherwise "block" rules deny listed countries.</p>
    <form method="POST" action="{{ route('admin.countries.store') }}" class="glass rounded-xl p-4 grid md:grid-cols-4 gap-3 items-end mb-5">
        @csrf
        <div><label>Country code (ISO2)</label><input name="country_code" maxlength="2" class="uppercase" required></div>
        <div><label>Name</label><input name="country_name"></div>
        <div><label>Mode</label><select name="mode"><option value="block">Block</option><option value="allow">Allow only</option></select></div>
        <button class="btn">Add rule</button>
    </form>
    <div class="glass rounded-xl p-4">
        <table>
            <tr><th>Code</th><th>Name</th><th>Mode</th><th></th></tr>
            @forelse ($rules as $rule)
                <tr><td>{{ $rule->country_code }}</td><td>{{ $rule->country_name }}</td><td>{{ ucfirst($rule->mode) }}</td>
                    <td><form method="POST" action="{{ route('admin.countries.destroy', $rule) }}">@csrf @method('DELETE')<button class="text-red-300">Remove</button></form></td></tr>
            @empty
                <tr><td colspan="4" class="text-gray-500">No restrictions — available everywhere.</td></tr>
            @endforelse
        </table>
    </div>
@endsection
