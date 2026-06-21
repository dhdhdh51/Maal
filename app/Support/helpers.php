<?php

use App\Models\Setting;
use Illuminate\Support\Number;

if (! function_exists('setting')) {
    /**
     * Read a platform setting (cached) with an optional default.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('money')) {
    /**
     * Format a monetary amount for display.
     */
    function money(float|int|string $amount, ?string $currency = null): string
    {
        $currency = $currency ?: (string) config('payments.currency', 'INR');

        try {
            return Number::currency((float) $amount, in: $currency);
        } catch (Throwable) {
            return $currency.' '.number_format((float) $amount, 2);
        }
    }
}

if (! function_exists('cdn_url')) {
    /**
     * Build a public CDN URL for a stored media path, falling back to the
     * application URL when no CDN is configured.
     */
    function cdn_url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $base = rtrim((string) config('streaming.cdn_url'), '/');

        if ($base !== '') {
            return $base.'/'.ltrim($path, '/');
        }

        return url('/'.ltrim($path, '/'));
    }
}

if (! function_exists('maintenance_active')) {
    /**
     * Settings-driven maintenance mode (independent of `artisan down`).
     */
    function maintenance_active(): bool
    {
        return (bool) setting('maintenance_mode', false);
    }
}
