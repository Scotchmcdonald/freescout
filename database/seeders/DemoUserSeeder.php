<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;

/**
 * Seed persistent demo / sandbox users for each role type.
 *
 * These are idempotent — they use firstOrCreate, so running the seeder
 * multiple times is safe. Every user is flagged `is_demo = true` so
 * PruneDemoAccounts can still clean them up, but because they use a
 * well-known email they can be exempted if desired.
 *
 * Usage:
 *   php artisan db:seed --class=DemoUserSeeder
 */
class DemoUserSeeder extends Seeder
{
    /** Shared password for all sandbox accounts */
    private const PASSWORD = 'DemoSandbox123!';

    /** Email domain for sandbox accounts */
    private const DOMAIN = 'sandbox.local';

    public function run(): void
    {
        $this->command->info('Seeding demo sandbox accounts…');

        // 1. Create a shared sandbox company
        $company = Company::firstOrCreate(
            ['email' => 'info@'.self::DOMAIN],
            [
                'name' => 'Demo Sandbox Inc.',
                'is_active' => true,
            ]
        );

        // 2. Create one user per internal role
        $definitions = [
            [
                'role' => UserRole::Admin,
                'email' => 'demo-admin@'.self::DOMAIN,
                'first_name' => 'Demo',
                'last_name' => 'Admin',
            ],
            [
                'role' => UserRole::User,
                'email' => 'demo-agent@'.self::DOMAIN,
                'first_name' => 'Demo',
                'last_name' => 'Agent',
            ],
            [
                'role' => UserRole::Finance,
                'email' => 'demo-finance@'.self::DOMAIN,
                'first_name' => 'Demo',
                'last_name' => 'Finance',
            ],
            [
                'role' => UserRole::Reporter,
                'email' => 'demo-reporter@'.self::DOMAIN,
                'first_name' => 'Demo',
                'last_name' => 'Reporter',
            ],
        ];

        $firstUser = null;

        foreach ($definitions as $def) {
            $user = User::firstOrCreate(
                ['email' => $def['email']],
                [
                    'first_name' => $def['first_name'],
                    'last_name' => $def['last_name'],
                    'role' => $def['role']->value,
                    'password' => Hash::make(self::PASSWORD),
                    'status' => User::STATUS_ACTIVE,
                    'invite_state' => User::INVITE_STATE_ACTIVATED,
                    'is_demo' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Set primary_contact_id on company to first user created (Admin)
            if ($firstUser === null) {
                $firstUser = $user;
                if (! $company->primary_contact_id) {
                    $company->update(['primary_contact_id' => $user->id]);
                }
            }

            // Attach user ↔ company if not already linked
            try {
                if (! $company->users()->where('user_id', $user->id)->exists()) {
                    $company->users()->attach($user->id, [
                        'role_id' => 1,
                        'status' => 'approved',
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('DemoUserSeeder: pivot attach failed', [
                    'user' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->command->line("  ✓ {$def['role']->label()}: {$user->email}");
        }

        // 3. Create a demo Client under the sandbox company
        $client = Client::firstOrCreate(
            ['email' => 'acme@'.self::DOMAIN],
            [
                'name' => 'Acme Logistics (Sandbox)',
                'company_id' => $company->id,
                'status' => 'active',
            ]
        );

        // 4. Create a Client Portal user (unified identity)
        try {
            $portalUser = User::firstOrCreate(
                ['email' => 'demo-portal@'.self::DOMAIN],
                [
                    'first_name' => 'Demo',
                    'last_name' => 'Portal User',
                    'type' => 2, // External / Client
                    'password' => Hash::make(self::PASSWORD),
                    'status' => User::STATUS_ACTIVE,
                    'invite_state' => User::INVITE_STATE_ACTIVATED,
                    'is_demo' => true,
                    'email_verified_at' => now(),
                ]
            );

            if (! $company->users()->where('user_id', $portalUser->id)->exists()) {
                $company->users()->attach($portalUser->id, [
                    'role_id' => 1,
                    'status' => 'approved',
                    'is_primary' => false,
                ]);
            }

            $this->command->line("  ✓ Client Portal: {$portalUser->email}");
        } catch (\Exception $e) {
            $this->command->warn("  ✗ Client Portal user failed: {$e->getMessage()}");
        }

        $this->command->newLine();
        $this->command->info('Sandbox accounts ready. Password for all: '.self::PASSWORD);
    }
}
