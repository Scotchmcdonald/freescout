<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

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

    /** @test */
    public function getFullName_returns_first_and_last_name(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $customer->getFullName());
    }

    /** @test */
    public function getFullName_trims_whitespace(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => '  John  ',
            'last_name' => '  Doe  ',
        ]);

        $this->assertEquals('John     Doe', $customer->getFullName());
    }

    /** @test */
    public function getFullName_with_only_first_name(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => '',
        ]);

        $this->assertEquals('John', $customer->getFullName());
    }

    /** @test */
    public function getFullName_with_only_last_name(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => '',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('Doe', $customer->getFullName());
    }

    /** @test */
    public function getFullName_with_empty_names(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => '',
            'last_name' => '',
        ]);

        $this->assertEquals('', $customer->getFullName());
    }

    /** @test */
    public function getFullName_with_unicode_characters(): void
    {
        $customer = Customer::factory()->withUnicodeName()->create();

        $this->assertStringContainsString('山田', $customer->getFullName());
        $this->assertStringContainsString('太郎', $customer->getFullName());
    }

    /** @test */
    public function getFullNameAttribute_returns_same_as_method(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $this->assertEquals($customer->getFullName(), $customer->full_name);
    }

    /** @test */
    public function getFirstName_returns_first_name(): void
    {
        $customer = Customer::factory()->create(['first_name' => 'Alice']);

        $this->assertEquals('Alice', $customer->getFirstName());
    }

    /** @test */
    public function getFirstName_returns_empty_string_when_null(): void
    {
        $customer = Customer::factory()->create(['first_name' => null]);

        $this->assertEquals('', $customer->getFirstName());
    }

    /** @test */
    public function getMainEmail_returns_primary_email(): void
    {
        $customer = Customer::factory()->create();
        
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

    /** @test */
    public function getMainEmail_returns_first_email_if_no_primary(): void
    {
        $customer = Customer::factory()->create();
        
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

    /** @test */
    public function getMainEmail_returns_null_when_no_emails(): void
    {
        $customer = Customer::factory()->create();
        $customer->emails()->delete(); // Remove auto-created email

        $this->assertNull($customer->getMainEmail());
    }

    /** @test */
    public function getPrimaryEmailAttribute_returns_primary_email(): void
    {
        $customer = Customer::factory()->create();
        
        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'primary@example.com',
            'type' => 1,
        ]);

        $this->assertEquals('primary@example.com', $customer->primary_email);
    }

    /** @test */
    public function getPrimaryEmailAttribute_returns_null_when_no_primary(): void
    {
        $customer = Customer::factory()->create();
        
        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'work@example.com',
            'type' => 2,
        ]);

        $this->assertNull($customer->primary_email);
    }

    /** @test */
    public function create_finds_existing_customer_by_email(): void
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

    /** @test */
    public function create_creates_new_customer_with_email(): void
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

    /** @test */
    public function create_sanitizes_email_address(): void
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

    /** @test */
    public function create_returns_null_for_invalid_email(): void
    {
        $customer = Customer::create('', ['first_name' => 'Test']);

        $this->assertNull($customer);
    }

    /** @test */
    public function create_does_not_overwrite_existing_data_by_default(): void
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

    /** @test */
    public function emails_relationship_returns_all_emails(): void
    {
        $customer = Customer::factory()->withMultipleEmails(3)->create();

        $this->assertCount(3, $customer->emails);
    }

    /** @test */
    public function conversations_relationship_loads(): void
    {
        $customer = Customer::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $customer->conversations());
    }

    /** @test */
    public function threads_relationship_loads(): void
    {
        $customer = Customer::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $customer->threads());
    }

    /** @test */
    public function channels_relationship_loads(): void
    {
        $customer = Customer::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $customer->channels());
    }

    /** @test */
    public function customer_has_required_fillable_fields(): void
    {
        $customer = new Customer();
        $fillable = $customer->getFillable();

        $this->assertContains('first_name', $fillable);
        $this->assertContains('last_name', $fillable);
        $this->assertContains('company', $fillable);
    }

    /** @test */
    public function customer_can_be_created_with_factory(): void
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

    /** @test */
    public function customer_with_emoji_name_saves_correctly(): void
    {
        $customer = Customer::factory()->withEmoji()->create();

        $this->assertStringContainsString('😀', $customer->first_name);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
        ]);
    }
}
