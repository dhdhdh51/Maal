<?php

use App\Http\Controllers\Api\AuthController;
use App\Models\Setting;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Stateless + Sanctum stateful endpoints. Grouped by system; individual
| systems register their routes here as they are built.
|
*/

Route::middleware('throttle:api')->group(function () {

    // Public runtime configuration consumed by the frontend.
    Route::get('/config', function () {
        $public = Setting::where('is_public', true)->get()
            ->mapWithKeys(fn (Setting $s) => [$s->key => $s->typedValue()]);

        return ApiResponse::success([
            'settings' => $public,
            'currency' => config('payments.currency'),
            'gateways' => collect(config('payments.gateways'))
                ->filter(fn ($g) => $g['enabled'] ?? false)
                ->map(fn ($g) => $g['label'])
                ->all(),
        ]);
    })->name('api.config');

    Route::get('/health', fn () => ApiResponse::success(['status' => 'ok', 'time' => now()->toIso8601String()]))
        ->name('api.health');

    // Authentication (token-based for mobile/SPA clients).
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
        Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('api.auth.verify');
    });
});

// Authenticated API surface (token or stateful session).
Route::middleware(['auth:sanctum', 'account.active', 'throttle:api'])->group(function () {
    Route::get('/me', fn (Request $r) => ApiResponse::success([
        'user' => $r->user()->only(['id', 'name', 'email', 'is_premium', 'country']),
        'roles' => $r->user()->getRoleNames(),
    ]))->name('api.me');

    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
});
