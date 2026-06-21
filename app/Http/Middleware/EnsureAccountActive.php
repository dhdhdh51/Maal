<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks suspended/banned accounts from authenticated areas.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            Auth::guard('web')->logout();

            if ($request->expectsJson()) {
                return ApiResponse::error('Your account has been '.$user->status.'.', 403);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been '.$user->status.'. Contact support.']);
        }

        return $next($request);
    }
}
