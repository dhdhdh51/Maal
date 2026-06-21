<?php

use App\Http\Controllers\PlaybackController;
use App\Http\Controllers\StreamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Public site routes. The cinematic frontend + catalog routes are added by
| later systems; for now a temporary home keeps named routes resolvable.
|
*/

Route::get('/', fn () => view('home-placeholder'))->name('home');

/*
| Streaming endpoints
|--------------------------------------------------------------------------
| HLS is authorised by an opaque path token; single assets by signed URL.
*/
Route::get('/stream/hls/{token}/{file}', [StreamController::class, 'hls'])
    ->where('file', '.+')->name('stream.hls');
Route::get('/stream/asset', [StreamController::class, 'asset'])
    ->middleware('signed')->name('stream.asset');

/*
| Watch page + playback control
*/
Route::middleware('age.confirmed')->group(function () {
    Route::get('/watch/{video:slug}', [PlaybackController::class, 'watch'])->name('watch');
    Route::get('/watch/{video:slug}/manifest', [PlaybackController::class, 'manifest'])->name('watch.manifest');

    Route::middleware('auth')->group(function () {
        Route::post('/watch/{video:slug}/heartbeat', [PlaybackController::class, 'heartbeat'])->name('watch.heartbeat');
        Route::post('/watch/{video:slug}/stopped', [PlaybackController::class, 'stopped'])->name('watch.stopped');
    });
});
