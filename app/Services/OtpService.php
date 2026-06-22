<?php

namespace App\Services;

use App\Models\OtpVerification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function __construct(protected Notifier $notifier) {}

    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /**
     * Issue a fresh OTP for a destination, invalidating prior unconsumed codes.
     */
    public function issue(string $channel, string $destination, string $purpose = 'verify', ?User $user = null): OtpVerification
    {
        // Invalidate previous active codes for this destination/purpose.
        OtpVerification::where('destination', $destination)
            ->where('channel', $channel)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = (string) random_int(100000, 999999);

        $otp = OtpVerification::create([
            'user_id' => $user?->id,
            'channel' => $channel,
            'destination' => $destination,
            'code_hash' => Hash::make($code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        $this->deliver($channel, $destination, $code, $purpose, $user);

        // Test-only seam: expose plaintext code to the test suite.
        if (app()->runningUnitTests()) {
            cache()->put("otp_test_{$channel}_{$destination}", $code, 600);
        }

        return $otp;
    }

    /**
     * Verify a submitted code. Returns true and consumes the OTP on success.
     */
    public function verify(string $channel, string $destination, string $code, string $purpose = 'verify'): bool
    {
        $otp = OtpVerification::where('destination', $destination)
            ->where('channel', $channel)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $otp || $otp->isExpired()) {
            return false;
        }

        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            $otp->update(['consumed_at' => now()]);

            return false;
        }

        $otp->increment('attempts');

        if (! $otp->verify($code)) {
            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }

    protected function deliver(string $channel, string $destination, string $code, string $purpose, ?User $user): void
    {
        if ($channel === 'email') {
            $this->notifier->sendTemplate('registration_verify', $destination, [
                'name' => $user?->name ?? 'there',
                'otp' => $code,
            ], $user?->name);

            return;
        }

        // Phone OTP – pluggable SMS provider. Logged when no provider configured.
        if (! setting('phone_otp_enabled', false) && ! config('app.debug')) {
            return;
        }

        Log::info("[SMS OTP] {$destination}: {$code} (purpose={$purpose})");
        // Integrate SMS provider here, e.g. via config('services.sms').
    }
}
