<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Referral;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\OtpService;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(protected OtpService $otp) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $referrer = ! empty($data['referral_code'])
                ? User::where('referral_code', $data['referral_code'])->first()
                : null;

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'status' => 'active',
                'age_confirmed' => true,
                'age_confirmed_at' => now(),
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
                'referred_by' => $referrer?->id,
            ]);

            $user->assignRole(Permissions::ROLE_USER);

            // Record a pending referral (reward granted on first paid order).
            if ($referrer) {
                Referral::create([
                    'referrer_id' => $referrer->id,
                    'referred_user_id' => $user->id,
                    'reward_type' => (string) setting('referral_reward_type', 'wallet_credit'),
                    'reward_value' => (float) setting('referral_reward_value', 0),
                    'status' => 'pending',
                ]);
            }

            return $user;
        });

        // Issue an email verification OTP.
        $this->otp->issue('email', $user->email, 'verify', $user);

        AuditLogger::log('user.registered', $user, 'New user registration', actor: $user);

        // Remember which account is mid-verification (not yet logged in).
        session(['otp.user_id' => $user->id, 'otp.email' => $user->email]);

        return redirect()->route('verification.notice')
            ->with('status', 'We sent a 6-digit code to '.$user->email.'.');
    }
}
