<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function __construct(protected AuthService $auth) {}

    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            AuditLogger::log('admin.login_failed', null, 'Failed admin login for '.$request->input('email'));

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->can('admin.access')) {
            Auth::logout();

            throw ValidationException::withMessages(['email' => 'This account does not have admin access.']);
        }

        if (! $user->isActive()) {
            Auth::logout();

            throw ValidationException::withMessages(['email' => 'Account is '.$user->status.'.']);
        }

        $this->auth->onAuthenticated($user, $request);
        $request->session()->regenerate();

        AuditLogger::log('admin.login', $user, 'Admin logged in', actor: $user);

        // Route through 2FA challenge when enabled.
        if ($user->two_factor_enabled) {
            $request->session()->forget('admin_2fa_passed');

            return redirect()->route('admin.2fa.challenge');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
