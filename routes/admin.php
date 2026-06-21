<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
|
| Prefixed with /admin and named admin.* (see bootstrap/app.php). Login + 2FA
| are defined here; the full panel is registered by the Admin Panel system.
|
*/

// Admin authentication (guest).
Route::middleware('guest')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->middleware('throttle:auth')->name('login.store');
});

// 2FA challenge — requires a password-authenticated session but not yet "passed".
Route::middleware(['auth', 'account.active'])->group(function () {
    Route::get('/two-factor', [TwoFactorController::class, 'challenge'])->name('2fa.challenge');
    Route::post('/two-factor', [TwoFactorController::class, 'verifyChallenge'])
        ->middleware('throttle:auth')->name('2fa.verify');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
});

// Protected admin panel (full surface added by the Admin Panel system).
Route::middleware(['auth', 'account.active', 'permission:admin.access', 'admin.2fa'])->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', fn () => view('admin.dashboard-placeholder'))->name('dashboard');
});

// Uploads (admins + content managers with videos.upload permission).
Route::middleware(['auth', 'account.active', 'permission:videos.upload', 'admin.2fa'])
    ->prefix('uploads')->name('uploads.')->group(function () {
        Route::get('/', [UploadController::class, 'index'])->name('index');

        Route::middleware('throttle:uploads')->group(function () {
            Route::post('/init', [UploadController::class, 'init'])->name('init');
            Route::post('/{token}/sign', [UploadController::class, 'sign'])->name('sign');
            Route::put('/{token}/parts/{partNumber}', [UploadController::class, 'chunk'])
                ->whereNumber('partNumber')->name('chunk');
            Route::post('/{token}/complete', [UploadController::class, 'complete'])->name('complete');
            Route::delete('/{token}', [UploadController::class, 'abort'])->name('abort');
        });

        Route::get('/{video}/status', [UploadController::class, 'status'])->name('status');
    });
