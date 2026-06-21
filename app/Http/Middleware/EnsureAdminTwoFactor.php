<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces admins to complete a 2FA challenge before reaching the admin panel
 * when 2FA is required by settings or enabled on their account.
 */
class EnsureAdminTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('admin.login');
        }

        $required = (bool) setting('admin_2fa_required', true) || $user->two_factor_enabled;

        if ($required && $user->two_factor_enabled && ! $request->session()->get('admin_2fa_passed')) {
            return redirect()->route('admin.2fa.challenge');
        }

        return $next($request);
    }
}
