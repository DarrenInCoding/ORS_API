<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@recyclesystem.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::ADMIN,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Branch Manager
        User::create([
            'name' => 'Branch Manager 1',
            'email' => 'manager@recyclesystem.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::BRANCH_MANAGER,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Staff
        User::create([
            'name' => 'Staff Member 1',
            'email' => 'staff@recyclesystem.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::STAFF,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Customer
        User::create([
            'name' => 'John Customer',
            'email' => 'customer@recyclesystem.com',
            'password' => Hash::make('password123'),
            'phone' => '0123456789',
            'role' => UserRole::CUSTOMER,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
