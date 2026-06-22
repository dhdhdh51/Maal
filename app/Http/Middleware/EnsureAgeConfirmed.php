<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the mandatory age-confirmation gate before browsing restricted
 * content. Authenticated users are checked against their stored flag;
 * guests are checked against a signed cookie set by the age gate page.
 */
class EnsureAgeConfirmed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! setting('age_gate_enabled', true)) {
            return $next($request);
        }

        $confirmed = false;

        if ($user = $request->user()) {
            $confirmed = (bool) $user->age_confirmed;
        }

        if (! $confirmed && $request->cookie('age_confirmed') === '1') {
            $confirmed = true;
        }

        if (! $confirmed) {
            if ($request->expectsJson()) {
                return ApiResponse::error('Age confirmation required.', 451, [
                    'age_gate' => true,
                ]);
            }

            return redirect()->route('age-gate', ['redirect' => $request->fullUrl()]);
        }

        return $next($request);
    }
}
