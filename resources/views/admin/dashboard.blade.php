@extends('admin.layout')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('content')
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach ([
            ['Users', number_format($overview['users']), '+'.$overview['new_users_30d'].' / 30d'],
            ['Revenue', money($overview['revenue_total']), money($overview['revenue_30d']).' / 30d'],
            ['Active subs', $overview['active_subscriptions'], $overview['expiring_7d'].' expiring 7d'],
            ['Watch hours', $overview['watch_hours'], ''],
            ['Videos ready', $overview['videos_ready'], $overview['videos_processing'].' processing'],
            ['Failed encodes', $overview['videos_failed'], ''],
            ['Failed payments', $overview['failed_payments'], $overview['refunds'].' refunds'],
            ['Preview → pay', $preview['conversion_rate'].'%', $preview['total_previews'].' previews'],
        ] as [$label, $value, $sub])
            <div class="glass rounded-xl p-4">
                <p class="text-xs text-gray-400">{{ $label }}</p>
                <p class="text-2xl font-bold mt-1">{{ $value }}</p>
                @if ($sub)<p class="text-xs text-gray-500 mt-0.5">{{ $sub }}</p>@endif
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 glass rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold">Revenue (30 days)</h2>
                <a href="{{ route('admin.dashboard.export') }}" class="btn-ghost btn">Export CSV</a>
            </div>
            <div class="flex items-end gap-1 h-40">
                @php($max = max(1, $revenue->max('total')))
                @foreach ($revenue as $r)
                    <div class="flex-1 bg-gradient-to-t from-violet-600 to-cyan-400 rounded-t" style="height: {{ max(2, $r['total'] / $max * 100) }}%" title="{{ $r['date'] }}: {{ money($r['total']) }}"></div>
                @endforeach
            </div>
        </div>
        <div class="glass rounded-xl p-4">
            <h2 class="font-semibold mb-3">Preview insights</h2>
            <ul class="text-sm space-y-2 text-gray-300">
                <li>Most converted: <span class="text-gray-100">{{ $preview['most_converted_category'] ?? '—' }}</span></li>
                <li>Top preview: <span class="text-gray-100">{{ $preview['most_watched_preview'] ?? '—' }}</span></li>
                <li>Abandoned checkouts: <span class="text-gray-100">{{ $preview['abandoned_checkouts'] }}</span></li>
            </ul>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mt-6">
        <div class="glass rounded-xl p-4">
            <h2 class="font-semibold mb-3">Top videos</h2>
            <table>
                <tr><th>Title</th><th>Views</th><th>Previews</th></tr>
                @foreach ($topVideos as $v)
                    <tr><td>{{ $v->title }}</td><td>{{ number_format($v->views_count) }}</td><td>{{ number_format($v->preview_plays_count) }}</td></tr>
                @endforeach
            </table>
        </div>
        <div class="glass rounded-xl p-4">
            <h2 class="font-semibold mb-3">Top categories by sales</h2>
            <table>
                <tr><th>Category</th><th>Revenue</th><th>Sales</th></tr>
                @foreach ($topCategories as $c)
                    <tr><td>{{ $c['category'] }}</td><td>{{ money($c['revenue']) }}</td><td>{{ $c['sales'] }}</td></tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection
