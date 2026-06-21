<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Centralises device fingerprinting, registration, the per-account device
 * limit and active-session bookkeeping used by auth and playback control.
 */
class DeviceManager
{
    public const COOKIE = 'maal_device';

    /**
     * Resolve (or mint) the device fingerprint for the current request.
     */
    public function fingerprint(Request $request): string
    {
        $id = $request->cookie(self::COOKIE);

        if (! $id || ! is_string($id) || strlen($id) > 64) {
            $id = (string) Str::uuid();
        }

        return $id;
    }

    /**
     * Register/refresh a device for a user and return the device record.
     */
    public function register(User $user, Request $request, string $fingerprint): UserDevice
    {
        $agent = (string) $request->userAgent();

        return UserDevice::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $fingerprint],
            [
                'name' => $this->label($agent),
                'platform' => $this->platform($agent),
                'browser' => $this->browser($agent),
                'device_type' => $this->deviceType($agent),
                'ip_address' => $request->ip(),
                'country' => $request->header('CF-IPCountry') ?? $user->country,
                'last_active_at' => now(),
            ],
        );
    }

    /**
     * Whether the user is allowed to register one more device.
     */
    public function withinLimit(User $user, string $fingerprint): bool
    {
        $limit = $user->effectiveDeviceLimit();

        if ($limit <= 0) {
            return true; // unlimited
        }

        // Existing device is always allowed back in.
        if ($user->devices()->where('device_id', $fingerprint)->exists()) {
            return true;
        }

        return $user->devices()->count() < $limit;
    }

    /**
     * Open (or refresh) an application session row for the device.
     */
    public function startSession(User $user, UserDevice $device, Request $request, ?string $tokenId = null): UserSession
    {
        return UserSession::updateOrCreate(
            ['user_id' => $user->id, 'device_id' => $device->id, 'token_id' => $tokenId],
            [
                'ip_address' => $request->ip(),
                'country' => $request->header('CF-IPCountry') ?? $user->country,
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'last_activity_at' => now(),
                'revoked_at' => null,
            ],
        );
    }

    public function touch(UserSession $session): void
    {
        $session->forceFill(['last_activity_at' => now()])->saveQuietly();
    }

    /**
     * Detect concurrent playback from a different active session/device.
     */
    public function hasConcurrentPlayback(User $user, ?int $exceptSessionId = null): bool
    {
        return $user->sessions()
            ->whereNull('revoked_at')
            ->where('is_playing', true)
            ->when($exceptSessionId, fn ($q) => $q->where('id', '!=', $exceptSessionId))
            ->where('last_activity_at', '>=', now()->subMinutes(2))
            ->exists();
    }

    // ---- naive user-agent parsing (no external dependency) ----------------

    protected function deviceType(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            return 'tablet';
        }
        if (str_contains($ua, 'mobi') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }

        return 'desktop';
    }

    protected function platform(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad'), str_contains($ua, 'Mac OS') => 'Apple',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Unknown',
        };
    }

    protected function browser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'OPR'), str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Browser',
        };
    }

    protected function label(string $ua): string
    {
        return trim($this->browser($ua).' on '.$this->platform($ua));
    }
}
