<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function __construct(protected Google2FA $google2fa) {}

    /**
     * Show the 2FA setup screen with a fresh secret + otpauth URI.
     */
    public function setup(Request $request): View
    {
        $user = $request->user();

        $secret = $request->session()->get('2fa.setup_secret')
            ?: $this->google2fa->generateSecretKey();

        $request->session()->put('2fa.setup_secret', $secret);

        $issuer = (string) setting('site_name', config('app.name'));
        $otpauthUrl = $this->google2fa->getQRCodeUrl($issuer, $user->email, $secret);

        return view('auth.two-factor-setup', [
            'secret' => $secret,
            'otpauthUrl' => $otpauthUrl,
            'enabled' => (bool) $user->two_factor_enabled,
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        $secret = $request->session()->get('2fa.setup_secret');

        if (! $secret || ! $this->google2fa->verifyKey($secret, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'Invalid authenticator code.']);
        }

        $recovery = collect(range(1, 8))->map(fn () => Str::upper(Str::random(10)))->all();

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recovery,
        ])->save();

        $request->session()->forget('2fa.setup_secret');
        $request->session()->put('admin_2fa_passed', true);

        AuditLogger::log('user.2fa_enabled', $user, '2FA enabled', actor: $user);

        return redirect()->route('admin.dashboard')
            ->with('status', 'Two-factor authentication enabled.')
            ->with('recovery_codes', $recovery);
    }

    /**
     * 2FA challenge during admin login.
     */
    public function challenge(): View
    {
        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        $code = (string) $request->input('code');
        $passed = $user->two_factor_secret
            && $this->google2fa->verifyKey($user->two_factor_secret, $code);

        // Allow single-use recovery codes.
        if (! $passed && is_array($user->two_factor_recovery_codes)) {
            $codes = $user->two_factor_recovery_codes;
            if (($idx = array_search(Str::upper($code), $codes, true)) !== false) {
                unset($codes[$idx]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                $passed = true;
            }
        }

        if (! $passed) {
            throw ValidationException::withMessages(['code' => 'Invalid authentication code.']);
        }

        $request->session()->put('admin_2fa_passed', true);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $user = $request->user();

        $user->forceFill([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        $request->session()->forget('admin_2fa_passed');
        AuditLogger::log('user.2fa_disabled', $user, '2FA disabled', actor: $user);

        return back()->with('status', 'Two-factor authentication disabled.');
    }
}
