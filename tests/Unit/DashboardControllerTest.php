<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\DashboardController;
use Modules\WidgetRegistry\Services\WidgetRegistryService;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
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
