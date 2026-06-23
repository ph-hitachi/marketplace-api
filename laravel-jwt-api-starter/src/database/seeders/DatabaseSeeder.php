<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ─────────────────────────────────────────────────────────
        User::create([
            'name'      => 'Admin User',
            'email'     => 'admin@example.com',
            'password'  => Hash::make('Admin1234!'),
            'role'      => 'admin',
            'is_active' => true,
        ]);

        // ── Seller ────────────────────────────────────────────────────────
        User::create([
            'name'      => 'Seller User',
            'email'     => 'seller@example.com',
            'password'  => Hash::make('Seller1234!'),
            'role'      => 'seller',
            'is_active' => true,
        ]);

        // ── Customer ──────────────────────────────────────────────────────
        User::create([
            'name'      => 'Customer User',
            'email'     => 'customer@example.com',
            'password'  => Hash::make('Customer1234!'),
            'role'      => 'customer',
            'is_active' => true,
        ]);
    }
}
