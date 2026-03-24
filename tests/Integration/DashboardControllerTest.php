<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Http\Controllers\DashboardController;
use Tests\IntegrationTestCase;

class DashboardControllerTest extends IntegrationTestCase
{
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
}
