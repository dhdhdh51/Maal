<?php

namespace App\Http\Middleware;

use App\Services\DeviceManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures every visitor carries a stable device fingerprint cookie and keeps
 * the authenticated user's device "last active" timestamp fresh.
 */
class TrackUserDevice
{
    public function __construct(protected DeviceManager $devices) {}

    public function handle(Request $request, Closure $next): Response
    {
        $fingerprint = $this->devices->fingerprint($request);
        $request->attributes->set('device_fingerprint', $fingerprint);

        /** @var Response $response */
        $response = $next($request);

        if ($user = $request->user()) {
            $device = $this->devices->register($user, $request, $fingerprint);
            $request->attributes->set('user_device_id', $device->id);
        }

        // Persist the fingerprint for ~2 years.
        Cookie::queue(cookie(DeviceManager::COOKIE, $fingerprint, 60 * 24 * 730, httpOnly: true));

        return $response;
    }
}
