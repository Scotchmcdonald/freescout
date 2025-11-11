<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Http\Controllers\ModulesController;
use Illuminate\Http\Request;
use Tests\TestCase;

class ModulesControllerTest extends TestCase
{
    public function test_controller_can_be_instantiated(): void
    {
        $controller = new ModulesController;

        $this->assertInstanceOf(ModulesController::class, $controller);
    }

    public function test_index_returns_view(): void
    {
        $controller = new ModulesController;
        $view = $controller->index();

        $this->assertEquals('modules.index', $view->name());
    }

    public function test_index_passes_modules_array_to_view(): void
    {
        $controller = new ModulesController;
        $view = $controller->index();

        $this->assertArrayHasKey('modules', $view->getData());
        $this->assertIsArray($view->getData()['modules']);
    }

    public function test_enable_returns_error_for_non_existent_module(): void
    {
        $controller = new ModulesController;
        $request = Request::create('/modules/nonexistent/enable', 'POST');

        $response = $controller->enable($request, 'nonexistent');

        $this->assertEquals(404, $response->getStatusCode());
        $json = $response->getData(true);
        $this->assertEquals('error', $json['status']);
    }

    public function test_disable_returns_error_for_non_existent_module(): void
    {
        $controller = new ModulesController;
        $request = Request::create('/modules/nonexistent/disable', 'POST');

        $response = $controller->disable($request, 'nonexistent');

        $this->assertEquals(404, $response->getStatusCode());
        $json = $response->getData(true);
        $this->assertEquals('error', $json['status']);
    }

    public function test_delete_returns_error_for_non_existent_module(): void
    {
        $controller = new ModulesController;
        $request = Request::create('/modules/nonexistent/delete', 'DELETE');

        $response = $controller->delete($request, 'nonexistent');

        $this->assertEquals(404, $response->getStatusCode());
        $json = $response->getData(true);
        $this->assertEquals('error', $json['status']);
    }

    public function test_enable_returns_json_response(): void
    {
        $controller = new ModulesController;
        $request = Request::create('/modules/test/enable', 'POST');

        $response = $controller->enable($request, 'nonexistent');

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
    }

    public function test_disable_returns_json_response(): void
    {
        $controller = new ModulesController;
        $request = Request::create('/modules/test/disable', 'POST');

        $response = $controller->disable($request, 'nonexistent');

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
    }

    public function test_delete_returns_json_response(): void
    {
        $controller = new ModulesController;
        $request = Request::create('/modules/test/delete', 'DELETE');

        $response = $controller->delete($request, 'nonexistent');

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
    }
}
