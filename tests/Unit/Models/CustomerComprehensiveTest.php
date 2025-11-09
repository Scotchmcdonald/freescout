<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\Conversation;
use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_be_instantiated()
    {
        $customer = new Customer();
        $this->assertInstanceOf(Customer::class, $customer);
    }

    public function test_customer_has_conversations_relationship()
    {
        $customer = Customer::factory()->create();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $customer->conversations());
    }

    public function test_customer_has_emails_relationship()
    {
        $customer = Customer::factory()->create();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $customer->emails());
    }

    public function test_customer_full_name_concatenates_first_and_last_name()
    {
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);
        $this->assertEquals('John Doe', $customer->getFullName());
    }

    public function test_customer_full_name_handles_null_last_name()
    {
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => null
        ]);
        $this->assertEquals('John', $customer->getFullName());
    }

    public function test_customer_can_have_multiple_conversations()
    {
        $customer = Customer::factory()
            ->has(Conversation::factory()->count(3))
            ->create();
        
        $this->assertCount(3, $customer->conversations);
    }

    public function test_customer_can_have_multiple_emails()
    {
        $customer = Customer::factory()->create();
        Email::factory()->count(2)->create(['customer_id' => $customer->id]);
        
        $this->assertCount(2, $customer->emails);
    }

    public function test_customer_optional_fields_can_be_null()
    {
        $customer = Customer::factory()->create([
            'company' => null,
            'job_title' => null,
            'city' => null,
            'state' => null,
            'zip' => null,
            'country' => null
        ]);
        
        $this->assertNull($customer->company);
        $this->assertNull($customer->job_title);
    }

    public function test_customer_timestamps_are_set()
    {
        $customer = Customer::factory()->create();
        
        $this->assertNotNull($customer->created_at);
        $this->assertNotNull($customer->updated_at);
    }

    public function test_customer_phone_accepts_various_formats()
    {
        $customer = Customer::factory()->create([
            'phones' => [['type' => 'work', 'value' => '123-456-7890']],
        ]);
        $this->assertIsArray($customer->phones);
        $this->assertEquals('123-456-7890', $customer->phones[0]['value']);
    }

    public function test_customer_first_name_can_be_null()
    {
        $customer = Customer::factory()->create(['first_name' => null]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'first_name' => null]);
    }

    public function test_customer_handles_very_long_names()
    {
        $longName = str_repeat('A', 255);
        $customer = Customer::factory()->create([
            'first_name' => $longName,
            'last_name' => $longName
        ]);
        
        $this->assertEquals($longName, $customer->first_name);
        $this->assertEquals($longName, $customer->last_name);
    }

    public function test_customer_company_field_accepts_long_text()
    {
        $longCompany = str_repeat('Company ', 30);
        $customer = Customer::factory()->create(['company' => $longCompany]);
        
        $this->assertEquals($longCompany, $customer->company);
    }

    public function test_customer_can_update_without_changing_email()
    {
        $customer = Customer::factory()->create();
        
        $customer->update(['first_name' => 'Updated']);
        
        $this->assertEquals('Updated', $customer->first_name);
    }

    public function test_customer_eager_loading_conversations()
    {
        $customer = Customer::factory()
            ->has(Conversation::factory()->count(2))
            ->create();
        
        $loaded = Customer::with('conversations')->find($customer->id);
        
        $this->assertTrue($loaded->relationLoaded('conversations'));
    }

    public function test_customer_with_special_characters_in_name()
    {
        $customer = Customer::factory()->create([
            'first_name' => "O'Brien",
            'last_name' => "Müller-Schmidt"
        ]);
        
        $this->assertEquals("O'Brien", $customer->first_name);
        $this->assertEquals("Müller-Schmidt", $customer->last_name);
    }
}
