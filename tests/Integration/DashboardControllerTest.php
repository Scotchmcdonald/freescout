<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Http\Controllers\DashboardController;
use Tests\IntegrationTestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardControllerTest extends IntegrationTestCase
{
    use RefreshDatabase;

    public function test_controller_can_be_instantiated(): void
    {
        $controller = $this->app->make(DashboardController::class);

        $this->assertInstanceOf(DashboardController::class, $controller);
    }

    public function test_index_method_exists(): void
    {
        $controller = $this->app->make(DashboardController::class);

        $this->assertTrue(method_exists($controller, 'index'));
    }

    public function test_client_is_redirected_to_portal_dashboard(): void
    {
        $user = User::factory()->create([
            'type' => User::TYPE_CLIENT
        ]);
        
        $response = $this->actingAs($user)->get(route('dashboard'));
        
        $response->assertRedirect(route('portal.dashboard'));
    }
}
