<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use App\Models\Video;

/**
 * Central authority for "can this user fully access this content?" decisions.
 * Expanded by the payments/access system; kept dependency-light so playback,
 * catalog and checkout can all rely on it.
 */
class AccessService
{
    /**
     * Whether the category is open to everyone (free) or has no gating.
     */
    public function categoryIsFree(?Category $category): bool
    {
        return ! $category || $category->access_type === 'free';
    }

    /**
     * Whether the user currently holds active access to a category.
     */
    public function userHasCategoryAccess(?User $user, int $categoryId): bool
    {
        return $user?->hasCategoryAccess($categoryId) ?? false;
    }

    /**
     * Whether the user may watch the full video (not just the preview).
     */
    public function canWatchFull(?User $user, Video $video): bool
    {
        // Staff with management permissions can always watch for QA.
        if ($user?->can('videos.view')) {
            return true;
        }

        $category = $video->category;

        if ($this->categoryIsFree($category)) {
            return true;
        }

        // A video explicitly marked unlocked / not locked.
        if (! $video->is_locked) {
            return true;
        }

        return $category && $this->userHasCategoryAccess($user, $category->id);
    }
}
