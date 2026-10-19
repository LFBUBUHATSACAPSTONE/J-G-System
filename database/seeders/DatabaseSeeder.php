<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Development seed data. Packages are seeded by their own migration (from config/packages.php),
 * so only the two accounts are created here:
 *
 *   admin@example.com / password  role 'admin'  -> the admin panel (/admin)
 *   test@example.com  / password  role 'user'   -> the customer area
 *
 * Admin accounts are created by the owner only: sign-up always produces a 'user'.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );

        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'role' => User::ROLE_USER,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ],
        );
    }
}
