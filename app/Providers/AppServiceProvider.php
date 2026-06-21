<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureRateLimiters();
    }

    protected function configureModels(): void
    {
        // Catch lazy loading / bad mass-assignment early outside production.
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::unguard(false);
    }

    protected function configureRateLimiters(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id ?: $request->ip()));

        // Sensitive auth endpoints (login, OTP, password reset).
        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(8)->by($request->ip()),
            Limit::perMinute(12)->by((string) $request->input('email', $request->ip())),
        ]);

        // Payment webhooks – generous but bounded per source IP.
        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(300)->by($request->ip()));

        // Upload signing/progress endpoints.
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(240)
            ->by($request->user()?->id ?: $request->ip()));
    }
}
