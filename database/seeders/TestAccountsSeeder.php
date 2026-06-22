<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Platform Admin',
                'email' => env('SEED_ADMIN_EMAIL', 'admin@maal.test'),
                'password' => env('SEED_ADMIN_PASSWORD', 'Admin@12345'),
                'role' => Permissions::ROLE_ADMIN,
                'premium' => true,
            ],
            [
                'name' => 'Content Manager',
                'email' => env('SEED_MANAGER_EMAIL', 'manager@maal.test'),
                'password' => env('SEED_MANAGER_PASSWORD', 'Manager@12345'),
                'role' => Permissions::ROLE_CONTENT_MANAGER,
                'premium' => false,
            ],
            [
                'name' => 'Premium User',
                'email' => env('SEED_PREMIUM_EMAIL', 'premium@maal.test'),
                'password' => env('SEED_PREMIUM_PASSWORD', 'Premium@12345'),
                'role' => Permissions::ROLE_PREMIUM,
                'premium' => true,
            ],
            [
                'name' => 'Demo User',
                'email' => env('SEED_USER_EMAIL', 'user@maal.test'),
                'password' => env('SEED_USER_PASSWORD', 'User@12345'),
                'role' => Permissions::ROLE_USER,
                'premium' => false,
            ],
        ];

        foreach ($accounts as $acc) {
            $user = User::updateOrCreate(
                ['email' => $acc['email']],
                [
                    'name' => $acc['name'],
                    'password' => Hash::make($acc['password']),
                    'email_verified_at' => now(),
                    'status' => 'active',
                    'is_premium' => $acc['premium'],
                    'premium_until' => $acc['premium'] ? now()->addYear() : null,
                    'age_confirmed' => true,
                    'age_confirmed_at' => now(),
                    'terms_accepted' => true,
                    'terms_accepted_at' => now(),
                    'country' => 'IN',
                ],
            );

            $user->syncRoles([$acc['role']]);
        }
    }
}
