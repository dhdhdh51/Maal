<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Notifier;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * OTP-based password reset: request a code by email, then submit the code
 * together with the new password. Avoids exposing reset-token links.
 */
class PasswordResetController extends Controller
{
    public function __construct(
        protected OtpService $otp,
        protected Notifier $notifier,
    ) {}

    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->input('email'))->first();

        // Always behave identically to avoid account enumeration.
        if ($user) {
            $this->otp->issue('email', $user->email, 'reset', $user);
        }

        session(['reset.email' => $request->input('email')]);

        return redirect()->route('password.reset')
            ->with('status', 'If that email exists, a reset code has been sent.');
    }

    public function resetForm(Request $request): View
    {
        return view('auth.reset-password', [
            'email' => session('reset.email', $request->query('email')),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        if (! $this->otp->verify('email', $request->input('email'), $request->string('code'), 'reset')) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired code.']);
        }

        $user = User::where('email', $request->input('email'))->firstOrFail();
        $user->forceFill(['password' => Hash::make($request->string('password'))])->save();

        AuditLogger::log('user.password_reset', $user, 'Password reset via OTP', actor: $user);
        $this->notifier->sendTemplate('password_changed', $user->email, ['name' => $user->name], $user->name);

        $request->session()->forget('reset.email');

        return redirect()->route('login')->with('status', 'Password updated. You can now sign in.');
    }
}
