<?php

use App\Http\Controllers\Auth\AgeGateController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\VerifyOtpController;
use App\Http\Controllers\DeviceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes (web)
|--------------------------------------------------------------------------
*/

// Age gate — available to everyone (no auth, bypasses age middleware).
Route::get('/age-gate', [AgeGateController::class, 'show'])->name('age-gate');
Route::post('/age-gate', [AgeGateController::class, 'confirm'])->name('age-gate.confirm');

// Guest-only auth flows.
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:auth');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:auth');

    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendCode'])
        ->middleware('throttle:auth')->name('password.email');
    Route::get('/reset-password', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:auth')->name('password.update');
});

// Email verification (user not yet logged in; session carries pending user).
Route::get('/verify-email', [VerifyOtpController::class, 'notice'])->name('verification.notice');
Route::post('/verify-email', [VerifyOtpController::class, 'verify'])
    ->middleware('throttle:auth')->name('verification.verify');
Route::post('/verify-email/resend', [VerifyOtpController::class, 'resend'])
    ->middleware('throttle:auth')->name('verification.resend');

// Authenticated user area.
Route::middleware(['auth', 'account.active'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Device & session management.
    Route::get('/dashboard/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::delete('/dashboard/devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');
    Route::post('/dashboard/devices/logout-all', [DeviceController::class, 'logoutAll'])->name('devices.logout_all');

    // Optional phone verification.
    Route::post('/verify-phone/send', [VerifyOtpController::class, 'sendPhone'])
        ->middleware('throttle:auth')->name('verification.phone.send');
    Route::post('/verify-phone', [VerifyOtpController::class, 'verifyPhone'])
        ->middleware('throttle:auth')->name('verification.phone.verify');

    // Two-factor (used by admins; available to all authenticated users).
    Route::get('/two-factor/setup', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/two-factor/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('/two-factor/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
});
