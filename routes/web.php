<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlaybackController;
use App\Http\Controllers\PreviewAnalyticsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\WatchHistoryController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
| Public catalog
*/
Route::middleware('age.confirmed')->group(function () {
    Route::get('/categories', [CatalogController::class, 'categories'])->name('categories');
    Route::get('/search', [CatalogController::class, 'browse'])->name('search');
    Route::get('/category/{category:slug}', [CatalogController::class, 'category'])->name('category.show');
    Route::get('/video/{video:slug}', [CatalogController::class, 'video'])->name('video.show');
});

// Content reporting (guests allowed).
Route::get('/report', [ReportController::class, 'create'])->name('report.create');
Route::post('/report', [ReportController::class, 'store'])->middleware('throttle:auth')->name('report.store');

// Newsletter signup.
Route::post('/newsletter', [NewsletterController::class, 'subscribe'])->middleware('throttle:auth')->name('newsletter.subscribe');

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
        Route::post('/watch/{video:slug}/progress', [WatchHistoryController::class, 'store'])->name('watch.progress');
    });

    // Preview engagement analytics (guests + users).
    Route::post('/watch/{video:slug}/preview-event', [PreviewAnalyticsController::class, 'store'])->name('watch.preview_event');
});

/*
| Checkout, payments & access
|--------------------------------------------------------------------------
*/
Route::middleware('age.confirmed')->group(function () {
    Route::get('/unlock/{category:slug}', [CheckoutController::class, 'show'])->name('unlock.show');

    Route::middleware(['auth', 'account.active'])->group(function () {
        Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
        Route::post('/checkout/coupon', [CheckoutController::class, 'validateCoupon'])->name('checkout.coupon');
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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/profile', [DashboardController::class, 'profile'])->name('profile.edit');
    Route::put('/dashboard/profile', [DashboardController::class, 'updateProfile'])->name('profile.update');
    Route::put('/dashboard/password', [DashboardController::class, 'updatePassword'])->name('password.change');

    Route::get('/dashboard/access', [AccessController::class, 'index'])->name('access.index');
    Route::get('/dashboard/invoices', [AccessController::class, 'invoices'])->name('invoices.index');
    Route::get('/dashboard/invoices/{payment}', [AccessController::class, 'invoice'])->name('invoices.show');

    // Favorites / watchlist
    Route::get('/dashboard/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{video:slug}/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // Watch history
    Route::get('/dashboard/history', [WatchHistoryController::class, 'index'])->name('history.index');
    Route::delete('/dashboard/history/{history}', [WatchHistoryController::class, 'destroy'])->name('history.destroy');

    // Notifications
    Route::get('/dashboard/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read_all');

    // Support tickets
    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::get('/support/new', [SupportController::class, 'create'])->name('support.create');
    Route::post('/support', [SupportController::class, 'store'])->name('support.store');
    Route::get('/support/{ticket}', [SupportController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}/reply', [SupportController::class, 'reply'])->name('support.reply');
});
