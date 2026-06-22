<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\AuthService;
use App\Services\Notifier;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VerifyOtpController extends Controller
{
    public function __construct(
        protected OtpService $otp,
        protected AuthService $auth,
        protected Notifier $notifier,
    ) {}

    public function notice(Request $request): View|RedirectResponse
    {
        $email = session('otp.email') ?? $request->user()?->email;

        if (! $email) {
            return redirect()->route('login');
        }

        return view('auth.verify-email', ['email' => $email]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $userId = session('otp.user_id') ?? $request->user()?->id;
        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please log in again.']);
        }

        if (! $this->otp->verify('email', $user->email, $request->string('code'), 'verify')) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired code.']);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now()])->save();
            AuditLogger::log('user.email_verified', $user, actor: $user);
        }

        // Log the user in and run device/session bookkeeping.
        Auth::login($user);
        $this->auth->onAuthenticated($user, $request);
        $request->session()->regenerate();
        $request->session()->forget(['otp.user_id', 'otp.email']);

        return redirect()->route('home')->with('status', 'Your email has been verified.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $email = session('otp.email') ?? $request->user()?->email;
        $user = User::where('email', $email)->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $this->otp->issue('email', $user->email, 'verify', $user);
        }

        return back()->with('status', 'A new code has been sent.');
    }

    // ---- Optional phone verification (from dashboard) ---------------------

    public function sendPhone(Request $request): RedirectResponse
    {
        $request->validate(['phone' => ['required', 'string', 'max:20']]);
        $user = $request->user();
        $user->update(['phone' => $request->string('phone')]);

        $this->otp->issue('phone', $user->phone, 'verify', $user);

        return back()->with('status', 'A verification code was sent to your phone.');
    }

    public function verifyPhone(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);
        $user = $request->user();

        if (! $this->otp->verify('phone', $user->phone, $request->string('code'), 'verify')) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired code.']);
        }

        $user->forceFill(['phone_verified_at' => now()])->save();

        return back()->with('status', 'Your phone number has been verified.');
    }
}
