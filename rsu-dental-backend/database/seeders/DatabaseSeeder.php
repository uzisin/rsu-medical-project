<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // demo accounts, for local development only
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $accounts = [
            ['name' => 'System Admin', 'username' => 'admin', 'password' => 'admin123', 'role' => 'admin'],
            ['name' => 'Reception Desk', 'username' => 'reception', 'password' => 'reception123', 'role' => 'receptionist'],
            ['name' => 'Dr. Khalid Osman', 'username' => 'doctor', 'password' => 'doctor123', 'role' => 'doctor'],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(['username' => $account['username']], $account + ['status' => 'active']);
        }
    }
}