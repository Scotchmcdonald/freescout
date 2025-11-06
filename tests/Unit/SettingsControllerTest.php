<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\SettingsController;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    public function test_controller_can_be_instantiated(): void
    {
        $controller = new SettingsController();
        
        $this->assertInstanceOf(SettingsController::class, $controller);
    }

    public function test_index_method_exists(): void
    {
        $controller = new SettingsController();
        
        $this->assertTrue(method_exists($controller, 'index'));
    }

    public function test_update_method_exists(): void
    {
        $controller = new SettingsController();
        
        $this->assertTrue(method_exists($controller, 'update'));
    }

    public function test_email_method_exists(): void
    {
        $controller = new SettingsController();
        
        $this->assertTrue(method_exists($controller, 'email'));
    }
}