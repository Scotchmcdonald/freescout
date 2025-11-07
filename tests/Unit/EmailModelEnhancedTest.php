<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailModelEnhancedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function email_has_customer_relationship(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        $email = Email::factory()->create([
            'customer_id' => $customer->id,
        ]);

        // Act
        $result = $email->customer;

        // Assert
        $this->assertInstanceOf(Customer::class, $result);
        $this->assertEquals($customer->id, $result->id);
    }

    /** @test */
    public function email_is_primary_returns_true_for_type_1(): void
    {
        // Arrange
        $email = new Email(['type' => 1]);

        // Act
        $result = $email->isPrimary();

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function email_is_primary_returns_false_for_type_2(): void
    {
        // Arrange
        $email = new Email(['type' => 2]);

        // Act
        $result = $email->isPrimary();

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function email_is_secondary_returns_true_for_type_2(): void
    {
        // Arrange
        $email = new Email(['type' => 2]);

        // Act
        $result = $email->isSecondary();

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function email_is_secondary_returns_false_for_type_1(): void
    {
        // Arrange
        $email = new Email(['type' => 1]);

        // Act
        $result = $email->isSecondary();

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function email_sanitize_converts_mixed_case_to_lowercase(): void
    {
        // Arrange
        $email = 'Test.User@EXAMPLE.COM';

        // Act
        $result = Email::sanitizeEmail($email);

        // Assert
        $this->assertEquals('test.user@example.com', $result);
    }

    /** @test */
    public function email_sanitize_removes_trailing_dots(): void
    {
        // Arrange
        $email = 'user@example.com...';

        // Act
        $result = Email::sanitizeEmail($email);

        // Assert
        $this->assertEquals('user@example.com', $result);
    }

    /** @test */
    public function email_sanitize_removes_dots_before_at_symbol(): void
    {
        // Arrange
        $email = 'user...@example.com';

        // Act
        $result = Email::sanitizeEmail($email);

        // Assert
        $this->assertEquals('user@example.com', $result);
    }

    /** @test */
    public function email_sanitize_preserves_dots_in_local_part(): void
    {
        // Arrange
        $email = 'first.last@example.com';

        // Act
        $result = Email::sanitizeEmail($email);

        // Assert
        $this->assertEquals('first.last@example.com', $result);
    }

    /** @test */
    public function email_sanitize_returns_false_for_missing_at_symbol(): void
    {
        // Arrange
        $email = 'userexample.com';

        // Act
        $result = Email::sanitizeEmail($email);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function email_sanitize_returns_false_for_empty_string(): void
    {
        // Act
        $result = Email::sanitizeEmail('');

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function email_sanitize_returns_false_for_null(): void
    {
        // Act
        $result = Email::sanitizeEmail(null);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function email_sanitize_accepts_valid_simple_email(): void
    {
        // Arrange
        $email = 'user@example.com';

        // Act
        $result = Email::sanitizeEmail($email);

        // Assert
        $this->assertEquals('user@example.com', $result);
    }

    /** @test */
    public function email_sanitize_accepts_email_with_subdomain(): void
    {
        // Arrange
        $email = 'user@mail.example.com';

        // Act
        $result = Email::sanitizeEmail($email);

        // Assert
        $this->assertEquals('user@mail.example.com', $result);
    }

    /** @test */
    public function email_sanitize_accepts_email_with_plus_sign(): void
    {
        // Arrange
        $email = 'user+tag@example.com';

        // Act
        $result = Email::sanitizeEmail($email);

        // Assert
        $this->assertEquals('user+tag@example.com', $result);
    }

    /** @test */
    public function email_sanitize_accepts_email_with_numbers(): void
    {
        // Arrange
        $email = 'user123@example456.com';

        // Act
        $result = Email::sanitizeEmail($email);

        // Assert
        $this->assertEquals('user123@example456.com', $result);
    }

    /** @test */
    public function email_sanitize_accepts_email_with_hyphen(): void
    {
        // Arrange
        $email = 'user-name@ex-ample.com';

        // Act
        $result = Email::sanitizeEmail($email);

        // Assert
        $this->assertEquals('user-name@ex-ample.com', $result);
    }

    /** @test */
    public function email_sanitize_accepts_email_with_underscore(): void
    {
        // Arrange
        $email = 'user_name@example.com';

        // Act
        $result = Email::sanitizeEmail($email);

        // Assert
        $this->assertEquals('user_name@example.com', $result);
    }

    /** @test */
    public function email_sanitize_returns_false_for_only_at_symbol(): void
    {
        // Act
        $result = Email::sanitizeEmail('@');

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function email_sanitize_returns_false_for_at_at_start(): void
    {
        // Arrange
        $email = '@example.com';

        // Act
        $result = Email::sanitizeEmail($email);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function email_sanitize_returns_false_for_at_at_end(): void
    {
        // Arrange
        $email = 'user@';

        // Act
        $result = Email::sanitizeEmail($email);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function email_casts_attributes_correctly(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        $email = Email::factory()->create([
            'type' => '1',
            'customer_id' => (string) $customer->id,
        ]);

        // Act & Assert
        $this->assertIsInt($email->type);
        $this->assertIsInt($email->customer_id);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $email->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $email->updated_at);
    }

    /** @test */
    public function email_fillable_includes_expected_fields(): void
    {
        // Arrange
        $expectedFields = ['customer_id', 'email', 'type'];

        // Act
        $email = new Email();
        $fillable = $email->getFillable();

        // Assert
        foreach ($expectedFields as $field) {
            $this->assertContains($field, $fillable);
        }
    }

    /** @test */
    public function email_can_be_created_with_factory(): void
    {
        // Act
        $email = Email::factory()->create();

        // Assert
        $this->assertInstanceOf(Email::class, $email);
        $this->assertNotNull($email->id);
        $this->assertNotNull($email->email);
        $this->assertNotNull($email->customer_id);
    }

    /** @test */
    public function email_factory_creates_primary_email_by_default(): void
    {
        // Act
        $email = Email::factory()->create();

        // Assert
        $this->assertEquals(1, $email->type);
        $this->assertTrue($email->isPrimary());
    }

    /** @test */
    public function email_factory_can_create_secondary_email(): void
    {
        // Act
        $email = Email::factory()->secondary()->create();

        // Assert
        $this->assertEquals(2, $email->type);
        $this->assertTrue($email->isSecondary());
    }

    /** @test */
    public function email_sanitize_handles_unicode_characters(): void
    {
        // Arrange
        $email = 'Üser@example.com';

        // Act
        $result = Email::sanitizeEmail($email);

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('@example.com', $result);
    }

    /** @test */
    public function multiple_emails_can_belong_to_same_customer(): void
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $email1 = Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'primary@example.com',
            'type' => 1,
        ]);
        $email2 = Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'secondary@example.com',
            'type' => 2,
        ]);

        // Assert
        $this->assertEquals($customer->id, $email1->customer_id);
        $this->assertEquals($customer->id, $email2->customer_id);
        $this->assertCount(2, $customer->emails);
    }
}
