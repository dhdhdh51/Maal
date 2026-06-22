<?php

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        // Create all permissions
        foreach (Permissions::all() as $name) {
            Permission::findOrCreate($name, $guard);
        }

        // Admin - full access
        $admin = Role::findOrCreate(Permissions::ROLE_ADMIN, $guard);
        $admin->syncPermissions(Permissions::all());

        // Content Manager - scoped
        $manager = Role::findOrCreate(Permissions::ROLE_CONTENT_MANAGER, $guard);
        $manager->syncPermissions(Permissions::contentManager());

        // Premium user - no admin permissions, role used for entitlement gating
        Role::findOrCreate(Permissions::ROLE_PREMIUM, $guard);

        // Registered user
        Role::findOrCreate(Permissions::ROLE_USER, $guard);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
