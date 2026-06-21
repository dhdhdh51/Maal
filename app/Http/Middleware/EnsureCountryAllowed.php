<?php

namespace App\Http\Middleware;

use App\Models\CountryRestriction;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Country availability gate based on admin-configured allow/block lists.
 * The country is resolved from an upstream CDN/proxy header (e.g. Cloudflare
 * `CF-IPCountry`) or the authenticated user's stored country.
 */
class EnsureCountryAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        $country = $this->resolveCountry($request);

        if (! CountryRestriction::isCountryAllowed($country)) {
            if ($request->expectsJson()) {
                return ApiResponse::error('This service is not available in your region.', 451);
            }

            return response()->view('errors.geo-blocked', ['country' => $country], 451);
        }

        return $next($request);
    }

    protected function resolveCountry(Request $request): ?string
    {
        return $request->header('CF-IPCountry')
            ?? $request->header('X-Country-Code')
            ?? $request->user()?->country;
    }
}
