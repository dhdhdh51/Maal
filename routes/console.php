<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Remove abandoned multipart uploads and stale "uploading" video records hourly.
Schedule::command('maal:cleanup-uploads')->hourly();

// Expire elapsed category-access grants daily.
Schedule::command('maal:expire-access')->daily();
