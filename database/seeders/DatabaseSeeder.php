<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin Account
        User::firstOrCreate(
            ['email' => 'admin@orderhub.com'],
            [
                'name' => 'Store Admin',
                'password' => Hash::make('password123'),
                'role' => UserRole::ADMIN,
                'is_active' => true,
            ]
        );

        // 2. Cashier Account
        User::firstOrCreate(
            ['email' => 'cashier@orderhub.com'],
            [
                'name' => 'Front Cashier',
                'password' => Hash::make('password123'),
                'role' => UserRole::CASHIER,
                'is_active' => true,
            ]
        );

        // 3. Kitchen Account
        User::firstOrCreate(
            ['email' => 'kitchen@orderhub.com'],
            [
                'name' => 'Kitchen Chef',
                'password' => Hash::make('password123'),
                'role' => UserRole::KITCHEN,
                'is_active' => true,
            ]
        );

        // 4. Menu & Modifiers
        $this->call(MenuSeeder::class);
    }
}
