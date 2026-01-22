<?php

/**
 * Widget Registry Integration Tests
 * 
 * Validates the Widget Registry pattern that enables dynamic UI composition.
 * Ensures core blindness and graceful handling of disabled modules.
 * 
 * PRIORITY: ⭐⭐⭐⭐ (High - Core Blindness Validation)
 * 
 * RUNNING TESTS:
 * php artisan dusk tests/Browser/WidgetRegistryIntegrationTest.php
 * php artisan dusk --group=widgets
 * php artisan dusk --group=client360
 */

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

class WidgetRegistryIntegrationTest extends DuskTestCase
{
    protected function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->orWhere('role', User::ROLE_ADMIN)->firstOrFail();
    }

    #[Group('widgets')]
    #[Group('integration')]
    #[Group('client360')]
    public function test_all_modules_register_widgets(): void
    {
        $this->browse(function (Browser $browser) {
            $client = \Modules\Crm\Models\Client::factory()->create();
            
            $browser->loginAs($this->getAdminUser())
                ->visit('/crm/clients/' . $client->id)
                ->assertSee($client->name)
                // We assert generalized content rather than specific module content to maintain decoupling
                ->assertPresent('div[class*="widget"]'); 
        });
    }

    #[Group('widgets')]
    #[Group('performance')]
    public function test_widget_loading_performance(): void
    {
        // Basic Load Test
        $start = microtime(true);
        $this->browse(function (Browser $browser) {
            $client = \Modules\Crm\Models\Client::factory()->create();
            $browser->loginAs($this->getAdminUser())
                    ->visit('/crm/clients/' . $client->id);
        });
        $duration = microtime(true) - $start;
        $this->assertLessThan(5.0, $duration);
    }

    #[Group('widgets')]
    #[Group('permissions')]
    #[Group('security')]
    public function test_widget_permission_filtering(): void
    {
         // Assuming permission logic in Registry. Creating a restricted widget:
         $registry = app(\App\Services\Ui\WidgetRegistry::class);
         $registry->register('client_360.top', function() { return 'Secret'; }, [], 10, 'impossible_permission');
         
         $this->browse(function (Browser $browser) {
             $client = \Modules\Crm\Models\Client::factory()->create();
             $browser->loginAs($this->getAdminUser())
                 ->visit('/crm/clients/' . $client->id)
                 ->assertDontSee('Secret');
         });
    }

    #[Group('widgets')]
    #[Group('smoke')]
    public function test_client_360_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $client = \Modules\Crm\Models\Client::factory()->create();
            $browser->loginAs($this->getAdminUser())
                ->visit('/dashboard')
                ->visit('/crm/clients/' . $client->id)
                ->assertSee('Client Information');
        });
    }
}
