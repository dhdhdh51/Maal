<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected DeviceManager $devices,
        protected Notifier $notifier,
    ) {}

    /**
     * Run post-authentication bookkeeping: device registration + limit
     * enforcement, session tracking, login metadata, anomaly alerting.
     *
     * @throws ValidationException when the device limit is exceeded.
     */
    public function onAuthenticated(User $user, Request $request, ?string $tokenId = null): void
    {
        $fingerprint = $this->devices->fingerprint($request);

        $isNewDevice = ! $user->devices()->where('device_id', $fingerprint)->exists();

        if (! $this->devices->withinLimit($user, $fingerprint)) {
            throw ValidationException::withMessages([
                'email' => 'Device limit reached for this account. Remove a device from your dashboard or contact support.',
            ]);
        }

        $device = $this->devices->register($user, $request, $fingerprint);
        $this->devices->startSession($user, $device, $request, $tokenId);

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        if ($isNewDevice) {
            $this->alertNewDevice($user, $request, $device->name);
        }
    }

    /**
     * Whether the user must satisfy a 2FA challenge after password auth.
     */
    public function needsTwoFactor(User $user): bool
    {
        return $user->two_factor_enabled && ! empty($user->two_factor_secret);
    }

    protected function alertNewDevice(User $user, Request $request, ?string $deviceName): void
    {
        if (setting('login_anomaly_alerts', true)) {
            $this->notifier->sendTemplate('new_device_login', $user->email, [
                'name' => $user->name,
                'device' => $deviceName ?? 'a new device',
                'ip' => (string) $request->ip(),
                'country' => (string) ($request->header('CF-IPCountry') ?? $user->country ?? '—'),
            ], $user->name);
        }

        $this->notifier->notify(
            $user,
            'new_device_login',
            'New device sign-in',
            'A new sign-in was detected from '.($deviceName ?? 'a new device').'.',
            route('devices.index'),
            'shield',
        );
    }
}
