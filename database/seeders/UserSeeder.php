<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing users (development only)
        if (app()->environment('local')) {
            User::query()->delete();
        }

        // Superadmin - Full access
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password_hash' => 'Password123', // Will be hashed by mutator
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        // Admin - CRUD access, no delete
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password_hash' => 'Password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Operator - Inactive (untuk testing login validation)
        User::create([
            'name' => 'Operator User',
            'email' => 'operator@example.com',
            'password_hash' => 'Password123',
            'role' => 'operator',
            'is_active' => false, // INACTIVE untuk testing
        ]);

        // Validator - Active
        User::create([
            'name' => 'Validator User',
            'email' => 'validator@example.com',
            'password_hash' => 'Password123',
            'role' => 'validator',
            'is_active' => true,
        ]);

        // Additional test users
        User::create([
            'name' => 'Test Admin',
            'email' => 'test.admin@example.com',
            'password_hash' => 'Password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->command->info('✅ Users seeded successfully!');
        $this->command->table(
            ['Name', 'Email', 'Role', 'Active'],
            User::all(['name', 'email', 'role', 'is_active'])->toArray()
        );
    }
}
