<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Services\OtpService;
use App\Support\ApiResponse;
use App\Support\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $auth,
        protected OtpService $otp,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

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
        ]);

        $user->assignRole(Permissions::ROLE_USER);
        $this->otp->issue('email', $user->email, 'verify', $user);

        return ApiResponse::success(
            ['user_id' => $user->id, 'verification_required' => true],
            'Registered. Verify your email with the code we sent.',
            201,
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return ApiResponse::error('Invalid credentials.', 422);
        }

        if (! $user->isActive()) {
            return ApiResponse::error('Your account has been '.$user->status.'.', 403);
        }

        if (! $user->hasVerifiedEmail()) {
            $this->otp->issue('email', $user->email, 'verify', $user);

            return ApiResponse::error('Email not verified. A new code has been sent.', 409, ['verification_required' => true]);
        }

        if ($this->auth->needsTwoFactor($user)) {
            return ApiResponse::error('Two-factor authentication required.', 409, ['two_factor_required' => true]);
        }

        // Device-limit enforcement (throws ValidationException -> 422 JSON).
        $this->auth->onAuthenticated($user, $request);

        $device = $request->attributes->get('device_fingerprint', 'api');
        $token = $user->createToken('api-'.$device, ['*'], now()->addDays(30));

        return ApiResponse::success([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'user' => $user->only(['id', 'name', 'email', 'is_premium', 'country']),
            'roles' => $user->getRoleNames(),
        ], 'Logged in.');
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        if (! $this->otp->verify('email', $request->input('email'), $request->string('code'), 'verify')) {
            return ApiResponse::error('Invalid or expired code.', 422);
        }

        $user = User::where('email', $request->input('email'))->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();

        return ApiResponse::success(null, 'Email verified. You can now log in.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logged out.');
    }
}
