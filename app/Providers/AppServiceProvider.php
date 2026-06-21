<?php

namespace App\Providers;

use App\Events\PaymentCompleted;
use App\Listeners\ApplyCouponRedemption;
use App\Listeners\DebitWalletForPayment;
use App\Listeners\GrantReferralReward;
use App\Services\Transcoding\FfmpegProcessor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FfmpegProcessor::class, function () {
            return FfmpegProcessor::fromConfig();
        });
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureRateLimiters();
        $this->registerEventListeners();
    }

    protected function registerEventListeners(): void
    {
        Event::listen(
            PaymentCompleted::class,
            ApplyCouponRedemption::class,
        );
        Event::listen(
            PaymentCompleted::class,
            DebitWalletForPayment::class,
        );
        Event::listen(
            PaymentCompleted::class,
            GrantReferralReward::class,
        );
    }

    protected function configureModels(): void
    {
        $strict = ! $this->app->isProduction();

        // Catch N+1 lazy loads and silently-dropped attributes outside
        // production, but allow reading not-yet-loaded attributes (returns
        // null) so freshly-created models stay ergonomic.
        Model::preventLazyLoading($strict);
        Model::preventSilentlyDiscardingAttributes($strict);
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
