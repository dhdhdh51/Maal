<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\AuthService;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(protected AuthService $auth) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = (bool) $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            AuditLogger::log('auth.login_failed', null, 'Failed login for '.$request->input('email'));

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->isActive()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Your account has been '.$user->status.'. Contact support.',
            ]);
        }

        // Require verified email before granting a session.
        if (! $user->hasVerifiedEmail()) {
            Auth::logout();
            session(['otp.user_id' => $user->id, 'otp.email' => $user->email]);
            app(OtpService::class)->issue('email', $user->email, 'verify', $user);

            return redirect()->route('verification.notice')
                ->with('status', 'Please verify your email. A new code has been sent.');
        }

        // Enforce device limit + session tracking + new-device alerts.
        $this->auth->onAuthenticated($user, $request);

        $request->session()->regenerate();

        AuditLogger::log('auth.login', $user, 'User logged in', actor: $user);

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            AuditLogger::log('auth.logout', $user, 'User logged out', actor: $user);
        }

        return redirect()->route('home');
    }
}
