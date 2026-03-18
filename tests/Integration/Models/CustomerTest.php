<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Customer;
use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test Customer model methods
 *
 * Focus: Name handling, email retrieval, customer creation
 */
class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_full_name_returns_first_and_last_name(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $customer->getFullName());
    }

    public function test_get_full_name_trims_whitespace(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => '  John  ',
            'last_name' => '  Doe  ',
        ]);

        $this->assertEquals('John     Doe', $customer->getFullName());
    }

    public function test_get_full_name_with_only_first_name(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => '',
        ]);

        $this->assertEquals('John', $customer->getFullName());
    }

    public function test_get_full_name_with_only_last_name(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => '',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('Doe', $customer->getFullName());
    }

    public function test_get_full_name_with_empty_names(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => '',
            'last_name' => '',
        ]);

        $this->assertEquals('', $customer->getFullName());
    }

    public function test_get_full_name_with_unicode_characters(): void
    {
        $customer = Customer::factory()->withUnicodeName()->create();

        $this->assertStringContainsString('山田', $customer->getFullName());
        $this->assertStringContainsString('太郎', $customer->getFullName());
    }

    public function test_get_full_name_attribute_returns_same_as_method(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $this->assertEquals($customer->getFullName(), $customer->full_name);
    }

    public function test_get_first_name_returns_first_name(): void
    {
        $customer = Customer::factory()->create(['first_name' => 'Alice']);

        $this->assertEquals('Alice', $customer->getFirstName());
    }

    public function test_get_first_name_returns_empty_string_when_null(): void
    {
        $customer = Customer::factory()->create(['first_name' => null]);

        $this->assertEquals('', $customer->getFirstName());
    }

    public function test_get_main_email_returns_primary_email(): void
    {
        $customer = Customer::factory()->create();
        $customer->emails()->delete();

        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'primary@example.com',
            'type' => 1, // Primary
        ]);

        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'secondary@example.com',
            'type' => 2, // Secondary
        ]);

        $this->assertEquals('primary@example.com', $customer->getMainEmail());
    }

    public function test_get_main_email_returns_first_email_if_no_primary(): void
    {
        $customer = Customer::factory()->create();

        // Delete auto-created email from factory
        $customer->emails()->delete();

        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'first@example.com',
            'type' => 2,
        ]);

        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'second@example.com',
            'type' => 2,
        ]);

        $this->assertEquals('first@example.com', $customer->getMainEmail());
    }

    public function test_get_main_email_returns_null_when_no_emails(): void
    {
        $customer = Customer::factory()->create();
        $customer->emails()->delete(); // Remove auto-created email

        $this->assertNull($customer->getMainEmail());
    }

    public function test_get_primary_email_attribute_returns_primary_email(): void
    {
        $customer = Customer::factory()->create();
        $customer->emails()->delete();

        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'primary@example.com',
            'type' => 1,
        ]);

        $this->assertEquals('primary@example.com', $customer->primary_email);
    }

    public function test_get_primary_email_attribute_returns_null_when_no_primary(): void
    {
        $customer = Customer::factory()->create();
        $customer->emails()->delete();

        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'work@example.com',
            'type' => 2,
        ]);

        $this->assertNull($customer->primary_email);
    }

    public function test_create_finds_existing_customer_by_email(): void
    {
        $existingCustomer = Customer::factory()->create();
        Email::factory()->create([
            'customer_id' => $existingCustomer->id,
            'email' => 'existing@example.com',
            'type' => 1,
        ]);

        $customer = Customer::create('existing@example.com', [
            'first_name' => 'Updated',
        ]);

        $this->assertEquals($existingCustomer->id, $customer->id);
    }

    public function test_create_creates_new_customer_with_email(): void
    {
        $customer = Customer::create('newcustomer@example.com', [
            'first_name' => 'New',
            'last_name' => 'Customer',
        ]);

        $this->assertNotNull($customer);
        $this->assertEquals('New', $customer->first_name);
        $this->assertEquals('Customer', $customer->last_name);
        $this->assertDatabaseHas('emails', [
            'customer_id' => $customer->id,
            'email' => 'newcustomer@example.com',
            'type' => 1,
        ]);
    }

    public function test_create_sanitizes_email_address(): void
    {
        $customer = Customer::create('  TEST@EXAMPLE.COM  ', [
            'first_name' => 'Test',
        ]);

        $this->assertNotNull($customer);
        $this->assertDatabaseHas('emails', [
            'customer_id' => $customer->id,
            'email' => 'test@example.com',
        ]);
    }

    public function test_create_returns_null_for_invalid_email(): void
    {
        $customer = Customer::create('', ['first_name' => 'Test']);

        $this->assertNull($customer);
    }

    public function test_create_does_not_overwrite_existing_data_by_default(): void
    {
        $existing = Customer::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Name',
        ]);

        Email::factory()->create([
            'customer_id' => $existing->id,
            'email' => 'test@example.com',
            'type' => 1,
        ]);

        $customer = Customer::create('test@example.com', [
            'first_name' => 'NewName',
        ]);

        // Refresh to get latest data
        $customer->refresh();

        $this->assertEquals($existing->id, $customer->id);
        $this->assertEquals('Original', $customer->first_name); // Should NOT be overwritten
    }

    public function test_emails_relationship_returns_all_emails(): void
    {
        $customer = Customer::factory()->withMultipleEmails(3)->create();

        $this->assertCount(3, $customer->emails);
    }

    public function test_customer_has_required_fillable_fields(): void
    {
        $customer = new Customer;
        $fillable = $customer->getFillable();

        $this->assertContains('first_name', $fillable);
        $this->assertContains('last_name', $fillable);
        $this->assertContains('company', $fillable);
    }

    public function test_customer_can_be_created_with_factory(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Factory',
            'last_name' => 'Test',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'Factory',
            'last_name' => 'Test',
        ]);
    }

    public function test_customer_with_emoji_name_saves_correctly(): void
    {
        $customer = Customer::factory()->withEmoji()->create();

        $this->assertStringContainsString('😀', $customer->first_name);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
        ]);
    }
}
