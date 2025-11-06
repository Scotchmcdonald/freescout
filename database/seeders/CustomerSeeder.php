<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Email;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        Customer::factory()
            ->count(20)
            ->create()
            ->each(function (Customer $customer) {
                // Create primary email
                Email::factory()->create([
                    'customer_id' => $customer->id,
                    'type' => 1, // Primary
                ]);

                // Sometimes add secondary emails
                if (fake()->boolean(30)) {
                    Email::factory()->secondary()->create([
                        'customer_id' => $customer->id,
                    ]);
                }
            });
    }
}
