<?php

namespace App\Support;

/**
 * Central registry of platform permissions and the five user roles.
 *
 * Roles: guest (no DB role - unauthenticated), user, premium, content_manager, admin.
 */
class Permissions
{
    // Roles
    public const ROLE_USER = 'user';

    public const ROLE_PREMIUM = 'premium';

    public const ROLE_CONTENT_MANAGER = 'content_manager';

    public const ROLE_ADMIN = 'admin';

    /**
     * All permissions grouped by domain.
     *
     * @return array<string, array<int, string>>
     */
    public static function groups(): array
    {
        return [
            'dashboard' => [
                'admin.access',          // can reach the admin panel at all
                'analytics.view',
                'system.health.view',
            ],
            'categories' => [
                'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            ],
            'videos' => [
                'videos.view', 'videos.create', 'videos.update', 'videos.delete',
                'videos.upload', 'videos.publish', 'videos.disable', 'videos.reprocess',
                'subtitles.manage', 'preview.manage',
            ],
            'payments' => [
                'payments.view', 'payments.refund', 'payments.manual_approve',
                'plans.manage', 'coupons.manage',
            ],
            'access' => [
                'access.grant', 'access.revoke',
            ],
            'users' => [
                'users.view', 'users.update', 'users.suspend', 'users.force_logout',
                'roles.manage',
            ],
            'moderation' => [
                'reports.view', 'reports.resolve',
            ],
            'support' => [
                'tickets.view', 'tickets.reply', 'tickets.assign',
            ],
            'content' => [
                'banners.manage', 'homepage.manage', 'pages.manage', 'emails.manage',
                'notifications.send',
            ],
            'settings' => [
                'settings.manage', 'storage.manage', 'gateways.manage',
                'countries.manage', 'maintenance.toggle', 'audit.view', 'backup.manage',
            ],
        ];
    }

    /**
     * Flat list of every permission name.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_values(array_merge(...array_values(static::groups())));
    }

    /**
     * Permissions granted to the Content Manager role.
     *
     * @return array<int, string>
     */
    public static function contentManager(): array
    {
        return [
            'admin.access',
            'analytics.view',
            'categories.view',
            'videos.view', 'videos.create', 'videos.update', 'videos.upload',
            'videos.publish', 'videos.reprocess', 'subtitles.manage', 'preview.manage',
            'reports.view',
            'tickets.view', 'tickets.reply',
        ];
    }
}
