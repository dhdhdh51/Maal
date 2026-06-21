<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Video;
use App\Services\AuditLogger;
use App\Services\Notifier;
use App\Services\Storage\MediaStorage;
use App\Services\Transcoding\TranscodeManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VideoController extends Controller
{
    public function __construct(
        protected TranscodeManager $transcode,
        protected Notifier $notifier,
    ) {}

    public function index(Request $request): View
    {
        $videos = Video::query()
            ->with('category')
            ->when($request->q, fn ($q) => $q->where('title', 'like', '%'.$request->q.'%'))
            ->when($request->status, fn ($q) => $q->where('processing_status', $request->status))
            ->when($request->category, fn ($q) => $q->where('category_id', $request->category))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.videos.index', [
            'videos' => $videos,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Video $video): View
    {
        $video->load(['category', 'subtitles', 'processingJobs']);

        return view('admin.videos.edit', [
            'video' => $video,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Video $video): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'is_locked' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'preview_seconds' => ['nullable', 'integer', 'min:0'],
            'preview_start' => ['nullable', 'integer', 'min:0'],
            'watermark_enabled' => ['nullable', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $video->update([
            ...$data,
            'is_locked' => $request->boolean('is_locked'),
            'is_published' => $request->boolean('is_published'),
            'watermark_enabled' => $request->has('watermark_enabled') ? $request->boolean('watermark_enabled') : null,
            'published_at' => $request->boolean('is_published') && ! $video->published_at ? now() : $video->published_at,
        ]);

        AuditLogger::log('video.updated', $video, "Video {$video->title} updated");

        return back()->with('status', 'Video updated.');
    }

    /**
     * Bulk actions over a set of video ids.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:publish,unpublish,disable,restore,reprocess,delete'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $videos = Video::whereIn('id', $data['ids'])->get();

        foreach ($videos as $video) {
            match ($data['action']) {
                'publish' => $video->update(['is_published' => true, 'published_at' => $video->published_at ?? now()]),
                'unpublish' => $video->update(['is_published' => false]),
                'disable' => $this->disable($video),
                'restore' => $video->update(['is_disabled' => false]),
                'reprocess' => $this->transcode->reprocess($video),
                'delete' => $video->delete(),
            };
        }

        AuditLogger::log('video.bulk_'.$data['action'], null, count($data['ids']).' videos: '.$data['action']);

        return back()->with('status', ucfirst($data['action']).' applied to '.count($data['ids']).' videos.');
    }

    /**
     * One-click instant disable (immediately stops playback + delisting).
     */
    public function disableVideo(Video $video): RedirectResponse
    {
        $this->disable($video);

        return back()->with('status', 'Video disabled and removed from listings.');
    }

    public function restoreVideo(Video $video): RedirectResponse
    {
        $video->update(['is_disabled' => false]);
        AuditLogger::log('video.restored', $video, "Video {$video->title} restored");

        return back()->with('status', 'Video restored.');
    }

    public function reprocess(Video $video): RedirectResponse
    {
        $this->transcode->reprocess($video);
        AuditLogger::log('video.reprocess', $video, "Video {$video->title} reprocessing");

        return back()->with('status', 'Reprocessing queued.');
    }

    public function reprocessStage(Request $request, Video $video): RedirectResponse
    {
        $stage = $request->validate(['stage' => ['required', 'in:poster,thumbnails,preview,transcode']])['stage'];
        $this->transcode->reprocessStage($video, $stage);

        return back()->with('status', "Stage {$stage} re-queued.");
    }

    public function cancel(Video $video): RedirectResponse
    {
        $this->transcode->cancel($video);

        return back()->with('status', 'Processing cancelled.');
    }

    public function uploadSubtitle(Request $request, Video $video): RedirectResponse
    {
        $data = $request->validate([
            'language' => ['required', 'string', 'max:8'],
            'label' => ['nullable', 'string', 'max:60'],
            'file' => ['required', 'file', 'mimes:srt,vtt,txt'],
        ]);

        $format = $request->file('file')->getClientOriginalExtension() === 'vtt' ? 'vtt' : 'srt';
        $path = $request->file('file')->store('subtitles-src', $this->mediaDisk());

        $subtitle = $video->subtitles()->create([
            'language' => $data['language'],
            'label' => $data['label'] ?? strtoupper($data['language']),
            'format' => $format,
            'path' => $path,
            'is_ready' => false,
        ]);

        $this->transcode->processSubtitle($subtitle);

        return back()->with('status', 'Subtitle uploaded and queued for processing.');
    }

    protected function disable(Video $video): void
    {
        $video->update(['is_disabled' => true]);
        AuditLogger::log('video.disabled', $video, "Video {$video->title} disabled");

        if ($uploader = $video->uploader) {
            $this->notifier->notify($uploader, 'video_disabled', 'Your video was disabled',
                "“{$video->title}” has been disabled by moderation.", null, 'alert');
        }
    }

    protected function mediaDisk(): string
    {
        return app(MediaStorage::class)->diskName();
    }
}
