<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadInitRequest;
use App\Models\Category;
use App\Models\Video;
use App\Services\AuditLogger;
use App\Services\Storage\StorageQuota;
use App\Services\Storage\UploadService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UploadController extends Controller
{
    public function __construct(
        protected UploadService $uploads,
        protected StorageQuota $quota,
    ) {}

    /**
     * Drag-and-drop uploader page.
     */
    public function index(): View
    {
        return view('admin.uploads.index', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'partSizeMb' => max(5, (int) config('streaming.uploads.chunk_size_mb', 8)),
            'allowedFormats' => (array) config('streaming.uploads.allowed_formats', []),
            'quotaRemaining' => $this->quota->remainingBytes(),
        ]);
    }

    public function init(UploadInitRequest $request): JsonResponse
    {
        try {
            $session = $this->uploads->initiate($request->user(), $request->validated());
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($session, 'Upload initialised.');
    }

    public function sign(Request $request, string $token): JsonResponse
    {
        $request->validate(['part_number' => ['required', 'integer', 'min:1']]);

        try {
            $url = $this->uploads->signPart($token, (int) $request->input('part_number'));
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success(['url' => $url]);
    }

    /**
     * Local-disk chunk receiver (raw binary body).
     */
    public function chunk(Request $request, string $token, int $partNumber): JsonResponse
    {
        $contents = $request->getContent();

        if ($contents === '') {
            return ApiResponse::error('Empty chunk.', 422);
        }

        try {
            $this->uploads->storeLocalChunk($token, $partNumber, $contents);
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success(['part_number' => $partNumber]);
    }

    public function complete(Request $request, string $token): JsonResponse
    {
        $data = $request->validate([
            'parts' => ['array'],
            'parts.*.PartNumber' => ['required_with:parts', 'integer'],
            'parts.*.ETag' => ['required_with:parts', 'string'],
        ]);

        try {
            $video = $this->uploads->complete($token, $data['parts'] ?? []);
        } catch (\Throwable $e) {
            return ApiResponse::error('Failed to finalise upload: '.$e->getMessage(), 422);
        }

        AuditLogger::log('video.uploaded', $video, 'Video uploaded: '.$video->title);

        return ApiResponse::success([
            'video_id' => $video->id,
            'status' => $video->processing_status,
        ], 'Upload complete. Processing has been queued.');
    }

    public function abort(string $token): JsonResponse
    {
        $this->uploads->abort($token);

        return ApiResponse::success(null, 'Upload cancelled.');
    }

    /**
     * Lightweight processing-status poll for the uploader UI.
     */
    public function status(Video $video): JsonResponse
    {
        return ApiResponse::success([
            'status' => $video->processing_status,
            'progress' => $video->processing_progress,
        ]);
    }
}
