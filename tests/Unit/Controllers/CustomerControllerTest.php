<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Http\Controllers\CustomerController;
use App\Models\Conversation;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_controller_can_be_instantiated(): void
    {
        $controller = new CustomerController;

        $this->assertInstanceOf(CustomerController::class, $controller);
    }

    public function test_index_returns_view(): void
    {
        $controller = new CustomerController;
        $request = Request::create('/customers', 'GET');

        $view = $controller->index($request);

        $this->assertEquals('customers.index', $view->name());
    }

    public function test_index_passes_customers_to_view(): void
    {
        Customer::factory()->count(3)->create();

        $controller = new CustomerController;
        $request = Request::create('/customers', 'GET');

        $view = $controller->index($request);

        $this->assertArrayHasKey('customers', $view->getData());
    }

    public function test_index_searches_by_first_name(): void
    {
        Customer::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        Customer::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $controller = new CustomerController;
        $request = Request::create('/customers?search=John', 'GET');

        $view = $controller->index($request);
        $customers = $view->getData()['customers'];

        $this->assertCount(1, $customers);
        $this->assertEquals('John', $customers->first()->first_name);
    }

    public function test_index_searches_by_last_name(): void
    {
        Customer::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        Customer::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $controller = new CustomerController;
        $request = Request::create('/customers?search=Smith', 'GET');

        $view = $controller->index($request);
        $customers = $view->getData()['customers'];

        $this->assertCount(1, $customers);
        $this->assertEquals('Smith', $customers->first()->last_name);
    }

    public function test_show_returns_view(): void
    {
        $customer = Customer::factory()->create();

        $controller = new CustomerController;
        $view = $controller->show($customer);

        $this->assertEquals('customers.show', $view->name());
        $this->assertArrayHasKey('customer', $view->getData());
    }

    public function test_show_loads_conversations(): void
    {
        $customer = Customer::factory()->create();
        Conversation::factory()->create(['customer_id' => $customer->id]);

        $controller = new CustomerController;
        $view = $controller->show($customer);

        $customer = $view->getData()['customer'];
        $this->assertTrue($customer->relationLoaded('conversations'));
    }

    public function test_edit_returns_view(): void
    {
        $customer = Customer::factory()->create();

        $controller = new CustomerController;
        $view = $controller->edit($customer);

        $this->assertEquals('customers.edit', $view->name());
    }

    public function test_update_returns_json_success_response(): void
    {
        $customer = Customer::factory()->create();

        $controller = new CustomerController;
        $request = Request::create('/customers/'.$customer->id, 'PUT');
        $request->merge([
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);

        $response = $controller->update($request, $customer);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Updated', $customer->fresh()->first_name);
    }

    public function test_update_accepts_optional_fields(): void
    {
        $customer = Customer::factory()->create();

        $controller = new CustomerController;
        $request = Request::create('/customers/'.$customer->id, 'PUT');
        $request->merge([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company' => 'Acme Inc',
            'job_title' => 'Developer',
            'city' => 'New York',
        ]);

        $response = $controller->update($request, $customer);

        $this->assertTrue($response->getData(true)['success']);
        $this->assertEquals('Acme Inc', $customer->fresh()->company);
        $this->assertEquals('Developer', $customer->fresh()->job_title);
        $this->assertEquals('New York', $customer->fresh()->city);
    }

    public function test_merge_combines_two_customers(): void
    {
        $source = Customer::factory()->create();
        $target = Customer::factory()->create();
        Conversation::factory()->create(['customer_id' => $source->id]);

        $controller = new CustomerController;
        $request = Request::create('/customers/merge', 'POST');
        $request->merge([
            'source_id' => $source->id,
            'target_id' => $target->id,
        ]);

        $response = $controller->merge($request);

        $this->assertTrue($response->getData(true)['success']);
        $this->assertDatabaseMissing('customers', ['id' => $source->id]);
        $this->assertEquals(1, Conversation::where('customer_id', $target->id)->count());
    }

    public function test_merge_prevents_merging_customer_with_itself(): void
    {
        $customer = Customer::factory()->create();

        $controller = new CustomerController;
        $request = Request::create('/customers/merge', 'POST');
        $request->merge([
            'source_id' => $customer->id,
            'target_id' => $customer->id,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->merge($request);
    }

    public function test_ajax_search_returns_matching_customers(): void
    {
        Customer::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        Customer::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

        $controller = new CustomerController;
        $request = Request::create('/customers/ajax', 'POST');
        $request->merge(['action' => 'search', 'q' => 'John']);

        $response = $controller->ajax($request);

        $data = $response->getData(true);
        $this->assertArrayHasKey('results', $data);
        $this->assertCount(1, $data['results']);
    }

    public function test_ajax_conversations_returns_customer_conversations(): void
    {
        $customer = Customer::factory()->create();
        Conversation::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'state' => 2,
        ]);

        $controller = new CustomerController;
        $request = Request::create('/customers/ajax', 'POST');
        $request->merge(['action' => 'conversations', 'customer_id' => $customer->id]);

        $response = $controller->ajax($request);

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertCount(3, $data['conversations']);
    }

    public function test_ajax_returns_error_for_invalid_action(): void
    {
        $controller = new CustomerController;
        $request = Request::create('/customers/ajax', 'POST');
        $request->merge(['action' => 'invalid']);

        $response = $controller->ajax($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
    }
}
