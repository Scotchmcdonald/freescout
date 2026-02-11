<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'role' => UserRole::Admin->value,
                'email' => config('app.admin_email', 'admin@example.com'),
                'password' => config('app.seeding.admin.password', 'admin123456789'),
                'first_name' => config('app.seeding.admin.first_name', 'System'),
                'last_name' => config('app.seeding.admin.last_name', 'Administrator'),
            ],
            [
                'role' => UserRole::User->value,
                'email' => config('app.seeding.agent.email', 'agent@example.com'),
                'password' => config('app.seeding.agent.password', 'agent123456789'),
                'first_name' => config('app.seeding.agent.first_name', 'Support'),
                'last_name' => config('app.seeding.agent.last_name', 'Agent'),
            ],
            [
                'role' => UserRole::Finance->value,
                'email' => config('app.seeding.finance.email', 'finance@example.com'),
                'password' => config('app.seeding.finance.password', 'finance123456789'),
                'first_name' => config('app.seeding.finance.first_name', 'Finance'),
                'last_name' => config('app.seeding.finance.last_name', 'Manager'),
            ],
            [
                'role' => UserRole::Reporter->value,
                'email' => config('app.seeding.reporter.email', 'reporter@example.com'),
                'password' => config('app.seeding.reporter.password', 'reporter123456789'),
                'first_name' => config('app.seeding.reporter.first_name', 'Report'),
                'last_name' => config('app.seeding.reporter.last_name', 'Viewer'),
            ],
        ];

        foreach ($users as $userData) {
            if (!User::where('email', $userData['email'])->exists()) {
                User::create([
                    'role' => $userData['role'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'status' => User::STATUS_ACTIVE,
                    'invite_state' => User::INVITE_STATE_ACTIVATED,
                    'email_verified_at' => now(),
                ]);
            }
        }
    }
}
