<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\CustomerController;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    public function test_controller_can_be_instantiated(): void
    {
        $controller = new CustomerController;

        $this->assertInstanceOf(CustomerController::class, $controller);
    }

    public function test_index_method_exists(): void
    {
        $controller = new CustomerController;

        $this->assertTrue(method_exists($controller, 'index'));
    }

    public function test_show_method_exists(): void
    {
        $controller = new CustomerController;

        $this->assertTrue(method_exists($controller, 'show'));
    }

    public function test_edit_method_exists(): void
    {
        $controller = new CustomerController;

        $this->assertTrue(method_exists($controller, 'edit'));
    }

    public function test_update_method_exists(): void
    {
        $controller = new CustomerController;

        $this->assertTrue(method_exists($controller, 'update'));
    }
}
