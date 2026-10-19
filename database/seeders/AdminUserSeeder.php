<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin1@gmail.com'],
            [
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'middle_initial' => 'S',
                'phone' => '09707386926',
                'address' => 'Pangclara, Candaba, Pampanga',
                'role' => 'admin',
                'password' => Hash::make('Pass123?'),
            ]
        );
    }
}
