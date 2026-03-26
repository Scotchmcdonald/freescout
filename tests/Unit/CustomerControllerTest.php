<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\CustomerController;
use Illuminate\Database\DatabaseManager;
use Tests\PureUnitTestCase;

class CustomerControllerTest extends PureUnitTestCase
{
    private function makeController(): CustomerController
    {
        return new CustomerController($this->createMock(DatabaseManager::class));
    }

    public function test_controller_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CustomerController::class, $this->makeController());
    }

    public function test_index_method_exists(): void
    {
        $this->assertTrue(method_exists($this->makeController(), 'index'));
    }

    public function test_show_method_exists(): void
    {
        $this->assertTrue(method_exists($this->makeController(), 'show'));
    }

    public function test_edit_method_exists(): void
    {
        $this->assertTrue(method_exists($this->makeController(), 'edit'));
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(method_exists($this->makeController(), 'update'));
    }
}
