<?php

namespace App\Services\Payments;

use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserCategoryAccess;
use App\Services\AuditLogger;
use Illuminate\Support\Carbon;

/**
 * Grants, extends, revokes and expires category access. The single source of
 * truth for entitlement changes (payments, admin grants, refunds, expiry).
 */
class AccessGrantService
{
    /**
     * Grant (or extend) access for every category covered by a paid order.
     */
    public function grantFromPayment(Payment $payment): void
    {
        [$categoryIds, $validityDays, $planId] = $this->coverage($payment);

        foreach ($categoryIds as $categoryId) {
            $this->grant(
                user: $payment->user,
                categoryId: $categoryId,
                validityDays: $validityDays,
                source: 'payment',
                planId: $planId,
                paymentId: $payment->id,
            );
        }

        $this->refreshPremium($payment->user);
    }

    /**
     * Core grant/extend operation. Extends an existing active grant rather
     * than duplicating it.
     */
    public function grant(
        User $user,
        int $categoryId,
        ?int $validityDays,
        string $source = 'payment',
        ?int $planId = null,
        ?int $paymentId = null,
        ?User $admin = null,
        ?string $note = null,
    ): UserCategoryAccess {
        $existing = $user->categoryAccess()
            ->where('category_id', $categoryId)
            ->where('status', 'active')
            ->first();

        $expiresAt = $this->computeExpiry($validityDays, $existing?->expires_at);

        if ($existing) {
            $existing->update([
                'expires_at' => $expiresAt,
                'plan_id' => $planId ?? $existing->plan_id,
                'payment_id' => $paymentId ?? $existing->payment_id,
            ]);
            $access = $existing;
        } else {
            $access = $user->categoryAccess()->create([
                'category_id' => $categoryId,
                'plan_id' => $planId,
                'payment_id' => $paymentId,
                'source' => $source,
                'status' => 'active',
                'granted_at' => now(),
                'expires_at' => $expiresAt,
                'granted_by' => $admin?->id,
                'note' => $note,
            ]);
        }

        AuditLogger::log('access.granted', $access,
            "Access to category #{$categoryId} ({$source})", actor: $admin);

        return $access;
    }

    public function adminGrant(User $user, Category $category, ?int $days, ?User $admin, ?string $note = null): UserCategoryAccess
    {
        $access = $this->grant($user, $category->id, $days, 'admin_grant', admin: $admin, note: $note);
        $this->refreshPremium($user);

        return $access;
    }

    /**
     * Revoke every active grant tied to a payment (e.g. on refund).
     */
    public function revokeForPayment(Payment $payment): void
    {
        UserCategoryAccess::where('payment_id', $payment->id)
            ->where('status', 'active')
            ->update(['status' => 'revoked']);

        if ($payment->user) {
            $this->refreshPremium($payment->user);
        }
    }

    public function revoke(UserCategoryAccess $access, ?User $admin = null): void
    {
        $access->update(['status' => 'revoked']);
        AuditLogger::log('access.revoked', $access, 'Access revoked', actor: $admin);
        $this->refreshPremium($access->user);
    }

    /**
     * Expire grants whose window has elapsed. Returns the number expired.
     */
    public function expireDue(): int
    {
        $due = UserCategoryAccess::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($due as $access) {
            $access->update(['status' => 'expired']);
            $this->refreshPremium($access->user);
        }

        return $due->count();
    }

    /**
     * Recompute the user's premium flag/expiry from active access.
     */
    public function refreshPremium(?User $user): void
    {
        if (! $user) {
            return;
        }

        $active = $user->categoryAccess()->active()->get();
        $hasLifetime = $active->contains(fn ($a) => is_null($a->expires_at));
        $maxExpiry = $active->max('expires_at');

        $user->forceFill([
            'is_premium' => $active->isNotEmpty(),
            'premium_until' => $hasLifetime ? null : $maxExpiry,
        ])->save();
    }

    /**
     * @return array{0: array<int>, 1: ?int, 2: ?int} [categoryIds, validityDays, planId]
     */
    protected function coverage(Payment $payment): array
    {
        if ($payment->plan) {
            return [$payment->plan->coveredCategoryIds(), $payment->plan->validity_days, $payment->plan->id];
        }

        if ($payment->category_id) {
            $category = $payment->category;

            return [[$payment->category_id], $category?->validity_days, null];
        }

        return [[], null, null];
    }

    protected function computeExpiry(?int $validityDays, ?Carbon $existingExpiry): ?Carbon
    {
        if ($validityDays === null) {
            return null; // lifetime
        }

        $base = $existingExpiry && $existingExpiry->isFuture() ? $existingExpiry : now();

        return $base->copy()->addDays($validityDays);
    }
}
