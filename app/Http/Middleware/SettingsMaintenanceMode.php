<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Settings-driven maintenance mode. Admins (with admin.access) bypass it so
 * they can still manage the platform while it is closed to the public.
 */
class SettingsMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (maintenance_active() && ! $request->user()?->can('admin.access')) {
            $message = (string) setting('maintenance_message', 'We will be back shortly.');

            if ($request->expectsJson()) {
                return ApiResponse::error($message, 503);
            }

            return response()->view('errors.maintenance', ['message' => $message], 503);
        }

        return $next($request);
    }
}
