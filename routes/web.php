<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlaybackController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\WebhookController;
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

/*
| Checkout, payments & access
|--------------------------------------------------------------------------
*/
Route::middleware('age.confirmed')->group(function () {
    Route::get('/unlock/{category:slug}', [CheckoutController::class, 'show'])->name('unlock.show');

    Route::middleware(['auth', 'account.active'])->group(function () {
        Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    });
});

// Gateway return (PayU/Razorpay post back here; CSRF-exempt).
Route::match(['get', 'post'], '/payment/return/{gateway}', [PaymentController::class, 'return'])->name('payment.return');
Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/failed', [PaymentController::class, 'failed'])->name('payment.failed');
Route::get('/payment/pending', [PaymentController::class, 'pending'])->name('payment.pending');

// Asynchronous webhooks (signature-verified + idempotent; CSRF-exempt).
Route::post('/webhooks/{gateway}', [WebhookController::class, 'handle'])
    ->middleware('throttle:webhooks')->name('webhooks');

// User access + invoices.
Route::middleware(['auth', 'account.active'])->group(function () {
    Route::get('/dashboard/access', [AccessController::class, 'index'])->name('access.index');
    Route::get('/dashboard/invoices', [AccessController::class, 'invoices'])->name('invoices.index');
    Route::get('/dashboard/invoices/{payment}', [AccessController::class, 'invoice'])->name('invoices.show');
});
