<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Central management of preview durations: the global default, per-category
 * overrides and per-video overrides.
 */
class PreviewController extends Controller
{
    public function index(): View
    {
        return view('admin.preview.index', [
            'globalDefault' => (int) setting('default_preview_seconds', 20),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'preview_seconds']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'global' => ['required', 'integer', 'min:0', 'max:600'],
            'categories' => ['array'],
            'categories.*' => ['nullable', 'integer', 'min:0', 'max:600'],
        ]);

        Setting::where('key', 'default_preview_seconds')->update(['value' => (string) $data['global']]);
        Cache::forget(Setting::CACHE_KEY);

        foreach (($data['categories'] ?? []) as $categoryId => $seconds) {
            Category::where('id', $categoryId)->update(['preview_seconds' => $seconds !== null && $seconds !== '' ? (int) $seconds : null]);
        }

        return back()->with('status', 'Preview durations updated.');
    }

    public function updateVideo(Request $request, Video $video): RedirectResponse
    {
        $data = $request->validate([
            'preview_seconds' => ['nullable', 'integer', 'min:0', 'max:600'],
            'preview_start' => ['nullable', 'integer', 'min:0'],
        ]);

        $video->update($data);

        return back()->with('status', 'Video preview settings updated.');
    }
}
