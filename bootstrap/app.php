<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureAdminTwoFactor;
use App\Http\Middleware\EnsureAgeConfirmed;
use App\Http\Middleware\EnsureCountryAllowed;
use App\Http\Middleware\SettingsMaintenanceMode;
use App\Http\Middleware\TrackUserDevice;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(__DIR__.'/../routes/auth.php');

            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(__DIR__.'/../routes/admin.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Force HTTPS in production when configured.
        if (env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        // Named middleware aliases
        $middleware->alias([
            'account.active' => EnsureAccountActive::class,
            'age.confirmed' => EnsureAgeConfirmed::class,
            'country.allowed' => EnsureCountryAllowed::class,
            'maintenance' => SettingsMaintenanceMode::class,
            'track.device' => TrackUserDevice::class,
            'admin.2fa' => EnsureAdminTwoFactor::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Country + maintenance + device tracking apply to all web traffic.
        $middleware->web(append: [
            EnsureCountryAllowed::class,
            SettingsMaintenanceMode::class,
            TrackUserDevice::class,
        ]);

        $middleware->api(append: [
            EnsureCountryAllowed::class,
        ]);

        // Sanctum stateful API for first-party SPA/mobile.
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
