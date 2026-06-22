<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CountryRestrictionController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\HomepageSectionController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PreviewController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VideoController;
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

// Protected admin panel.
Route::middleware(['auth', 'account.active', 'permission:admin.access', 'admin.2fa'])->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export-revenue', [DashboardController::class, 'exportRevenue'])->name('dashboard.export');

    // Categories + plans
    Route::middleware('permission:categories.view')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/categories/{category}/plans', [CategoryController::class, 'storePlan'])->name('categories.plans.store');
        Route::delete('/categories/{category}/plans/{plan}', [CategoryController::class, 'destroyPlan'])->name('categories.plans.destroy');
    });

    // Videos
    Route::middleware('permission:videos.view')->group(function () {
        Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
        Route::get('/videos/{video}/edit', [VideoController::class, 'edit'])->name('videos.edit');
        Route::put('/videos/{video}', [VideoController::class, 'update'])->name('videos.update');
        Route::post('/videos/bulk', [VideoController::class, 'bulk'])->name('videos.bulk');
        Route::post('/videos/{video}/disable', [VideoController::class, 'disableVideo'])->name('videos.disable');
        Route::post('/videos/{video}/restore', [VideoController::class, 'restoreVideo'])->name('videos.restore');
        Route::post('/videos/{video}/reprocess', [VideoController::class, 'reprocess'])->name('videos.reprocess');
        Route::post('/videos/{video}/reprocess-stage', [VideoController::class, 'reprocessStage'])->name('videos.reprocess_stage');
        Route::post('/videos/{video}/cancel', [VideoController::class, 'cancel'])->name('videos.cancel');
        Route::post('/videos/{video}/subtitles', [VideoController::class, 'uploadSubtitle'])->name('videos.subtitles');
    });

    // Preview durations
    Route::middleware('permission:videos.update')->group(function () {
        Route::get('/preview', [PreviewController::class, 'index'])->name('preview.index');
        Route::put('/preview', [PreviewController::class, 'update'])->name('preview.update');
        Route::put('/preview/video/{video}', [PreviewController::class, 'updateVideo'])->name('preview.video');
    });

    // Payments
    Route::middleware('permission:payments.view')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/{payment}/approve', [PaymentController::class, 'approve'])->middleware('permission:payments.manual_approve')->name('payments.approve');
        Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund'])->middleware('permission:payments.refund')->name('payments.refund');
    });

    // Coupons
    Route::middleware('permission:coupons.manage')->group(function () {
        Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
        Route::get('/coupons/create', [CouponController::class, 'create'])->name('coupons.create');
        Route::post('/coupons', [CouponController::class, 'store'])->name('coupons.store');
        Route::get('/coupons/{coupon}/edit', [CouponController::class, 'edit'])->name('coupons.edit');
        Route::put('/coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
        Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');
    });

    // Users + access
    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/force-logout', [UserController::class, 'forceLogout'])->name('users.force_logout');
        Route::post('/users/{user}/grant-access', [UserController::class, 'grantAccess'])->middleware('permission:access.grant')->name('users.grant');
        Route::delete('/users/{user}/access/{access}', [UserController::class, 'revokeAccess'])->middleware('permission:access.revoke')->name('users.revoke');
    });

    // Reports moderation
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::put('/reports/{report}', [ReportController::class, 'update'])->name('reports.update');
    });

    // Support tickets
    Route::middleware('permission:tickets.view')->group(function () {
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    });

    // Homepage sections
    Route::middleware('permission:homepage.manage')->group(function () {
        Route::get('/homepage', [HomepageSectionController::class, 'index'])->name('homepage.index');
        Route::post('/homepage', [HomepageSectionController::class, 'store'])->name('homepage.store');
        Route::put('/homepage/{section}', [HomepageSectionController::class, 'update'])->name('homepage.update');
        Route::delete('/homepage/{section}', [HomepageSectionController::class, 'destroy'])->name('homepage.destroy');
    });

    // Banners
    Route::middleware('permission:banners.manage')->group(function () {
        Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
        Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
        Route::put('/banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
        Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');
    });

    // Email templates
    Route::middleware('permission:emails.manage')->group(function () {
        Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
        Route::get('/email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
        Route::put('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
    });

    // Country restrictions
    Route::middleware('permission:countries.manage')->group(function () {
        Route::get('/countries', [CountryRestrictionController::class, 'index'])->name('countries.index');
        Route::post('/countries', [CountryRestrictionController::class, 'store'])->name('countries.store');
        Route::delete('/countries/{country}', [CountryRestrictionController::class, 'destroy'])->name('countries.destroy');
    });

    // Settings
    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/maintenance', [SettingController::class, 'toggleMaintenance'])->middleware('permission:maintenance.toggle')->name('settings.maintenance');
    });

    // Roles & permissions
    Route::middleware('permission:roles.manage')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });

    // Audit logs
    Route::get('/audit', [AuditLogController::class, 'index'])
        ->middleware('permission:audit.view')->name('audit.index');

    // System health
    Route::get('/health', [SystemHealthController::class, 'index'])
        ->middleware('permission:system.health.view')->name('health');
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
