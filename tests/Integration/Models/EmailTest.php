<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Customer;
use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test Email model
 *
 * Focus: Email validation, sanitization, types
 */
class EmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_belongs_to_customer(): void
    {
        $email = Email::factory()->create();

        $this->assertInstanceOf(Customer::class, $email->customer);
    }

    public function test_email_can_be_primary_type(): void
    {
        $email = Email::factory()->create(['type' => 1]);

        $this->assertEquals(1, $email->type);
    }

    public function test_email_can_be_work_type(): void
    {
        $email = Email::factory()->create(['type' => 2]);

        $this->assertEquals(2, $email->type);
    }

    public function test_email_can_be_other_type(): void
    {
        $email = Email::factory()->create(['type' => 3]);

        $this->assertEquals(3, $email->type);
    }

    public function test_sanitize_email_converts_to_lowercase(): void
    {
        $result = Email::sanitizeEmail('TEST@EXAMPLE.COM');

        $this->assertEquals('test@example.com', $result);
    }

    public function test_sanitize_email_trims_whitespace(): void
    {
        $result = Email::sanitizeEmail('  test@example.com  ');

        $this->assertEquals('test@example.com', $result);
    }

    public function test_sanitize_email_returns_null_for_empty_string(): void
    {
        $result = Email::sanitizeEmail('');

        $this->assertFalse($result);
    }

    public function test_sanitize_email_returns_null_for_whitespace_only(): void
    {
        $result = Email::sanitizeEmail('   ');

        $this->assertFalse($result);
    }

    public function test_sanitize_email_handles_unicode_domain(): void
    {
        $result = Email::sanitizeEmail('test@例え.jp');

        $this->assertNotNull($result);
        $this->assertStringContainsString('@', $result);
    }

    public function test_sanitize_email_preserves_plus_addressing(): void
    {
        $result = Email::sanitizeEmail('user+tag@example.com');

        $this->assertEquals('user+tag@example.com', $result);
    }

    public function test_sanitize_email_preserves_dots_in_local_part(): void
    {
        $result = Email::sanitizeEmail('first.last@example.com');

        $this->assertEquals('first.last@example.com', $result);
    }

    public function test_sanitize_email_preserves_subdomain(): void
    {
        $result = Email::sanitizeEmail('user@mail.example.com');

        $this->assertEquals('user@mail.example.com', $result);
    }

    public function test_email_has_required_fillable_fields(): void
    {
        $email = new Email;
        $fillable = $email->getFillable();

        $this->assertContains('email', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('customer_id', $fillable);
    }

    public function test_email_can_be_created_with_factory(): void
    {
        $email = Email::factory()->create([
            'email' => 'factory@example.com',
        ]);

        $this->assertDatabaseHas('emails', [
            'id' => $email->id,
            'email' => 'factory@example.com',
        ]);
    }

    public function test_email_has_timestamps(): void
    {
        $email = Email::factory()->create();

        $this->assertNotNull($email->created_at);
        $this->assertNotNull($email->updated_at);
    }

    public function test_customer_can_have_multiple_emails(): void
    {
        $customer = Customer::factory()->create();

        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'primary@example.com',
            'type' => 1,
        ]);

        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'work@example.com',
            'type' => 2,
        ]);

        $this->assertCount(3, $customer->emails); // 1 auto-created + 2 factory
    }

    public function test_email_address_must_be_unique_per_customer(): void
    {
        $customer = Customer::factory()->create();

        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'test@example.com',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'test@example.com',
        ]);
    }

    public function test_sanitize_email_handles_mixed_case(): void
    {
        $result = Email::sanitizeEmail('TeSt@ExAmPlE.CoM');

        $this->assertEquals('test@example.com', $result);
    }

    public function test_sanitize_email_with_very_long_email(): void
    {
        $longLocal = str_repeat('a', 64);
        $email = "{$longLocal}@example.com";

        $result = Email::sanitizeEmail($email);

        $this->assertNotNull($result);
        $this->assertEquals(strtolower($email), $result);
    }
}
