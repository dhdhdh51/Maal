<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\VideoProcessingJob;
use App\Services\Storage\StorageQuota;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function index(StorageQuota $quota): View
    {
        $checks = [
            'database' => $this->safe(fn () => DB::connection()->getPdo() !== null),
            'queue_pending' => $this->safe(fn () => DB::table('jobs')->count(), 0),
            'failed_jobs' => $this->safe(fn () => DB::table('failed_jobs')->count(), 0),
        ];

        return view('admin.health', [
            'checks' => $checks,
            'storageUsed' => $quota->usedBytes(),
            'storageMax' => $quota->maxBytes(),
            'failedTranscodes' => VideoProcessingJob::where('status', 'failed')->count(),
            'processingNow' => Video::whereIn('processing_status', ['processing', 'encoding', 'generating_preview'])->count(),
            'recentFailures' => VideoProcessingJob::where('status', 'failed')->with('video')->latest()->limit(10)->get(),
        ]);
    }

    protected function safe(callable $fn, mixed $fallback = false): mixed
    {
        try {
            return $fn();
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
