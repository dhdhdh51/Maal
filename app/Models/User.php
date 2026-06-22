<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'is_premium',
        'premium_until',
        'avatar',
        'country',
        'locale',
        'age_confirmed',
        'age_confirmed_at',
        'terms_accepted',
        'terms_accepted_at',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'device_limit',
        'last_login_at',
        'last_login_ip',
        'referral_code',
        'referred_by',
        'wallet_balance',
        'newsletter_opt_in',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'premium_until' => 'datetime',
            'age_confirmed_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'is_premium' => 'boolean',
            'age_confirmed' => 'boolean',
            'terms_accepted' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'newsletter_opt_in' => 'boolean',
            'wallet_balance' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->referral_code)) {
                $user->referral_code = static::generateReferralCode();
            }
        });
    }

    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    // ---------------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------------

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function categoryAccess(): HasMany
    {
        return $this->hasMany(UserCategoryAccess::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function watchHistory(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function uploadedVideos(): HasMany
    {
        return $this->hasMany(Video::class, 'uploaded_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasActivePremium(): bool
    {
        return $this->is_premium
            && (is_null($this->premium_until) || $this->premium_until->isFuture());
    }

    public function effectiveDeviceLimit(): int
    {
        return $this->device_limit ?? (int) config('streaming.devices.default_limit', 2);
    }

    public function hasCategoryAccess(int $categoryId): bool
    {
        return $this->categoryAccess()
            ->where('category_id', $categoryId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
