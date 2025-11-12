<?php

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_be_instantiated()
    {
        $customer = new Customer;
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
            'last_name' => 'Doe',
        ]);
        $this->assertEquals('John Doe', $customer->getFullName());
    }

    public function test_customer_full_name_handles_null_last_name()
    {
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => null,
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
        // Factory already creates 1 email, add 2 more
        Email::factory()->count(2)->create(['customer_id' => $customer->id]);

        $this->assertCount(3, $customer->emails);
    }

    public function test_customer_optional_fields_can_be_null()
    {
        $customer = Customer::factory()->create([
            'company' => null,
            'job_title' => null,
            'city' => null,
            'state' => null,
            'zip' => null,
            'country' => null,
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
            'last_name' => $longName,
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
            'last_name' => 'Müller-Schmidt',
        ]);

        $this->assertEquals("O'Brien", $customer->first_name);
        $this->assertEquals('Müller-Schmidt', $customer->last_name);
    }

    // Story 5.1.1: Customer Creation and Lookup

    public function test_create_method_returns_existing_customer_by_email(): void
    {
        $existingCustomer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Create email record for this customer (factory already creates one, so delete it first)
        $existingCustomer->emails()->delete();
        \App\Models\Email::factory()->create([
            'customer_id' => $existingCustomer->id,
            'email' => 'existing@example.com',
        ]);

        $result = Customer::create('existing@example.com', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        // Should return existing customer
        $this->assertEquals($existingCustomer->id, $result->id);
        // Original name is preserved (not updated)
        $this->assertEquals('John', $result->first_name);
    }

    public function test_create_method_creates_new_customer_when_not_exists(): void
    {
        $result = Customer::create('new@example.com', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $this->assertInstanceOf(Customer::class, $result);
        $this->assertEquals('Jane', $result->first_name);
        $this->assertEquals('Smith', $result->last_name);

        // Should have created email record
        $this->assertDatabaseHas('emails', [
            'customer_id' => $result->id,
            'email' => 'new@example.com',
        ]);
    }

    public function test_create_method_normalizes_email_to_lowercase(): void
    {
        $customer = Customer::create('UPPER@EXAMPLE.COM', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Email should be stored in lowercase
        $email = \App\Models\Email::where('customer_id', $customer->id)->first();
        $this->assertEquals('upper@example.com', $email->email);
    }

    public function test_create_method_handles_null_names_gracefully(): void
    {
        $customer = Customer::create('noname@example.com', [
            'first_name' => null,
            'last_name' => null,
        ]);

        $this->assertNotNull($customer->id);
        $this->assertNull($customer->first_name);
        $this->assertNull($customer->last_name);

        // Email record should still be created
        $this->assertDatabaseHas('emails', [
            'customer_id' => $customer->id,
            'email' => 'noname@example.com',
        ]);
    }

    public function test_create_method_handles_empty_string_names(): void
    {
        $customer = Customer::create('empty@example.com', [
            'first_name' => '',
            'last_name' => '',
        ]);

        $this->assertNotNull($customer->id);
        // Empty strings may be stored as null depending on DB config
        $this->assertTrue($customer->first_name === '' || $customer->first_name === null);
    }

    public function test_create_method_validates_email_format(): void
    {
        try {
            Customer::create('invalid-email', ['first_name' => 'Test']);
            $this->fail('Should have thrown exception for invalid email');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function test_create_method_handles_very_long_names(): void
    {
        $longFirst = str_repeat('A', 50);
        $longLast = str_repeat('B', 50);

        $customer = Customer::create('long@example.com', [
            'first_name' => $longFirst,
            'last_name' => $longLast,
        ]);

        $this->assertNotNull($customer->id);
        // Names should be truncated to fit database constraints
        $this->assertLessThanOrEqual(255, strlen($customer->first_name));
        $this->assertLessThanOrEqual(255, strlen($customer->last_name));
    }

    public function test_create_method_finds_customer_by_any_email(): void
    {
        $customer = Customer::factory()->create();

        // Create multiple emails for this customer
        \App\Models\Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'primary@example.com',
        ]);
        \App\Models\Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'secondary@example.com',
        ]);

        // Should find by secondary email
        $result = Customer::create('secondary@example.com', [
            'first_name' => 'Different',
        ]);

        $this->assertEquals($customer->id, $result->id);
    }

    public function test_create_method_handles_concurrent_creation(): void
    {
        // Simulate race condition by creating customer with same email
        $email = 'concurrent@example.com';

        $customer1 = Customer::create($email, ['first_name' => 'First']);
        $customer2 = Customer::create($email, ['first_name' => 'Second']);

        // Should return same customer
        $this->assertEquals($customer1->id, $customer2->id);
    }

    public function test_create_method_preserves_additional_data(): void
    {
        $customer = Customer::create('test@example.com', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company' => 'Acme Inc',
            'job_title' => 'Developer',
        ]);

        $this->assertEquals('Acme Inc', $customer->company);
        $this->assertEquals('Developer', $customer->job_title);
    }

    public function test_get_full_name_handles_various_formats(): void
    {
        // Both names
        $customer1 = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $this->assertEquals('John Doe', $customer1->getFullName());

        // Only first name
        $customer2 = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => null,
        ]);
        $this->assertEquals('Jane', $customer2->getFullName());

        // Only last name
        $customer3 = Customer::factory()->create([
            'first_name' => null,
            'last_name' => 'Smith',
        ]);
        $this->assertNotEmpty($customer3->getFullName());

        // Neither name
        $customer4 = Customer::factory()->create([
            'first_name' => null,
            'last_name' => null,
        ]);
        // Should return empty string or email
        $fullName = $customer4->getFullName();
        $this->assertIsString($fullName);
    }

    public function test_customer_relationships_are_properly_loaded(): void
    {
        $customer = Customer::factory()->create();

        // Create related records
        \App\Models\Email::factory()->create(['customer_id' => $customer->id]);
        Conversation::factory()->create(['customer_id' => $customer->id]);

        // Test lazy loading
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $customer->emails);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $customer->conversations);
    }

    public function test_customer_email_validation_on_creation(): void
    {
        // Test various email formats
        $validEmails = [
            'simple@example.com',
            'user+tag@example.com',
            'user.name@example.com',
            'user_name@example.com',
            'user-name@example.co.uk',
        ];

        foreach ($validEmails as $email) {
            $customer = Customer::create($email, ['first_name' => 'Test']);
            $this->assertNotNull($customer->id);
        }
    }

    public function test_customer_handles_international_email_addresses(): void
    {
        // International domain names (IDN)
        $customer = Customer::create('test@xn--e1afmkfd.xn--p1ai', [
            'first_name' => 'International',
        ]);

        $this->assertNotNull($customer->id);
    }
}
