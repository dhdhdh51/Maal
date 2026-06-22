<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media Storage Disk
    |--------------------------------------------------------------------------
    |
    | The disk used to store private original uploads and the generated HLS
    | renditions, posters, thumbnails and preview clips. Must point to one of
    | the disks defined in config/filesystems.php (local, s3, r2, b2, spaces).
    |
    */

    'media_disk' => env('MEDIA_DISK', 'r2'),

    /*
    |--------------------------------------------------------------------------
    | CDN
    |--------------------------------------------------------------------------
    |
    | Public CDN domain that fronts the media bucket. HLS playlists, segments,
    | posters and thumbnails are served through this domain. Leave empty to
    | serve through signed application routes instead.
    |
    */

    'cdn_url' => env('CDN_URL'),

    /*
    |--------------------------------------------------------------------------
    | Path Layout (within the media disk)
    |--------------------------------------------------------------------------
    */
    'paths' => [
        'originals' => 'originals',   // private source files (never public)
        'hls' => 'hls',               // adaptive renditions + playlists
        'posters' => 'posters',
        'thumbnails' => 'thumbnails',
        'previews' => 'previews',
        'subtitles' => 'subtitles',
        'temp' => 'tmp/uploads',      // resumable multipart staging
    ],

    /*
    |--------------------------------------------------------------------------
    | FFmpeg / FFprobe
    |--------------------------------------------------------------------------
    */
    'ffmpeg' => [
        'binary' => env('FFMPEG_BINARY', '/usr/bin/ffmpeg'),
        'ffprobe' => env('FFPROBE_BINARY', '/usr/bin/ffprobe'),
        'threads' => (int) env('FFMPEG_THREADS', 0),
        'timeout' => 3600 * 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | HLS Adaptive Renditions
    |--------------------------------------------------------------------------
    |
    | The ladder of qualities generated for each source. A rendition is only
    | produced when the source height is >= the rendition height, so a 720p
    | source will not be upscaled to 1080p/4K.
    |
    */
    'renditions' => [
        '360p' => ['height' => 360,  'width' => 640,  'v_bitrate' => '800k',  'a_bitrate' => '96k',  'maxrate' => '856k',   'bufsize' => '1200k'],
        '480p' => ['height' => 480,  'width' => 854,  'v_bitrate' => '1400k', 'a_bitrate' => '128k', 'maxrate' => '1498k',  'bufsize' => '2100k'],
        '720p' => ['height' => 720,  'width' => 1280, 'v_bitrate' => '2800k', 'a_bitrate' => '128k', 'maxrate' => '2996k',  'bufsize' => '4200k'],
        '1080p' => ['height' => 1080, 'width' => 1920, 'v_bitrate' => '5000k', 'a_bitrate' => '192k', 'maxrate' => '5350k',  'bufsize' => '7500k'],
        '4k' => ['height' => 2160, 'width' => 3840, 'v_bitrate' => '14000k', 'a_bitrate' => '192k', 'maxrate' => '14980k', 'bufsize' => '21000k'],
    ],

    'hls' => [
        'segment_seconds' => 6,
        'playlist_name' => 'master.m3u8',
        // Signed URL settings for protecting playlists/segments
        'signing_key' => env('HLS_SIGNING_KEY'),
        'url_ttl' => (int) env('HLS_URL_TTL', 14400),
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnails & Posters
    |--------------------------------------------------------------------------
    */
    'thumbnails' => [
        'count' => 6,          // evenly spaced sprite thumbnails
        'width' => 320,
    ],
    'poster' => [
        'width' => 1280,
    ],

    /*
    |--------------------------------------------------------------------------
    | Preview Clips
    |--------------------------------------------------------------------------
    |
    | Default preview length used when neither the video nor its category
    | overrides it. Resolution order at runtime: video > category > global.
    |
    */
    'preview' => [
        'default_seconds' => (int) env('DEFAULT_PREVIEW_SECONDS', 20),
        'default_start' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        // 0 = unlimited (governed by storage_configurations quota instead)
        'max_quota_gb' => (int) env('MAX_STORAGE_QUOTA_GB', 0),
        'chunk_size_mb' => (int) env('UPLOAD_CHUNK_SIZE_MB', 8),
        'allowed_formats' => array_filter(explode(',', env('ALLOWED_VIDEO_FORMATS', 'mp4,mov,mkv,webm,avi'))),
        'allowed_mimes' => [
            'video/mp4', 'video/quicktime', 'video/x-matroska',
            'video/webm', 'video/x-msvideo',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Watermark Defaults
    |--------------------------------------------------------------------------
    */
    'watermark' => [
        'enabled' => env('WATERMARK_ENABLED', false),
        'opacity' => (float) env('WATERMARK_OPACITY', 0.35),
        'move_interval' => (int) env('WATERMARK_MOVE_INTERVAL', 8),
        'font_size' => 14,
    ],

    /*
    |--------------------------------------------------------------------------
    | Device / Session Control
    |--------------------------------------------------------------------------
    */
    'devices' => [
        // 0 = unlimited
        'default_limit' => (int) env('DEFAULT_DEVICE_LIMIT', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Channels
    |--------------------------------------------------------------------------
    |
    | Dedicated queues so transcoding never blocks notifications/analytics.
    | Supervisor workers should be configured per channel (see README).
    |
    */
    'queues' => [
        'upload_validation' => 'uploads',
        'thumbnails' => 'thumbnails',
        'previews' => 'previews',
        'transcoding' => 'transcoding',
        'subtitles' => 'subtitles',
        'notifications' => 'notifications',
        'analytics' => 'analytics',
    ],

    'processing_states' => [
        'uploading' => 'Uploading',
        'uploaded' => 'Uploaded',
        'processing' => 'Processing',
        'generating_preview' => 'Generating Preview',
        'encoding' => 'Encoding',
        'ready' => 'Ready',
        'failed' => 'Failed',
        'retry' => 'Retry Processing',
    ],
];
