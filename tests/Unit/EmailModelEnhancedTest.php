<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Email;
use Tests\PureUnitTestCase;

/**
 * Pure-unit tests for the Email model.
 *
 * Covers isPrimary(), isSecondary(), sanitizeEmail(), attribute fillable list,
 * and basic attribute assignment.  DB-dependent tests (factory, relationships,
 * timestamp auto-population) are omitted and remain in the Integration suite.
 *
 * Migrated from tests/Integration/EmailModelEnhancedTest.php.
 */
class EmailModelEnhancedTest extends PureUnitTestCase
{
    // -------------------------------------------------------------------------
    // isPrimary / isSecondary
    // -------------------------------------------------------------------------

    public function test_email_is_primary_returns_true_for_type_1(): void
    {
        $email = new Email(['type' => 1]);

        $this->assertTrue($email->isPrimary());
    }

    public function test_email_is_primary_returns_false_for_type_2(): void
    {
        $email = new Email(['type' => 2]);

        $this->assertFalse($email->isPrimary());
    }

    public function test_email_is_secondary_returns_true_for_type_2(): void
    {
        $email = new Email(['type' => 2]);

        $this->assertTrue($email->isSecondary());
    }

    public function test_email_is_secondary_returns_false_for_type_1(): void
    {
        $email = new Email(['type' => 1]);

        $this->assertFalse($email->isSecondary());
    }

    // -------------------------------------------------------------------------
    // sanitizeEmail() — pure static method
    // -------------------------------------------------------------------------

    public function test_email_sanitize_converts_mixed_case_to_lowercase(): void
    {
        $this->assertEquals('test.user@example.com', Email::sanitizeEmail('Test.User@EXAMPLE.COM'));
    }

    public function test_email_sanitize_removes_trailing_dots(): void
    {
        $this->assertEquals('user@example.com', Email::sanitizeEmail('user@example.com...'));
    }

    public function test_email_sanitize_removes_dots_before_at_symbol(): void
    {
        $this->assertEquals('user@example.com', Email::sanitizeEmail('user...@example.com'));
    }

    public function test_email_sanitize_preserves_dots_in_local_part(): void
    {
        $this->assertEquals('first.last@example.com', Email::sanitizeEmail('first.last@example.com'));
    }

    public function test_email_sanitize_returns_false_for_missing_at_symbol(): void
    {
        $this->assertFalse(Email::sanitizeEmail('userexample.com'));
    }

    public function test_email_sanitize_returns_false_for_empty_string(): void
    {
        $this->assertFalse(Email::sanitizeEmail(''));
    }

    public function test_email_sanitize_returns_false_for_null(): void
    {
        $this->assertFalse(Email::sanitizeEmail(null));
    }

    public function test_email_sanitize_accepts_valid_simple_email(): void
    {
        $this->assertEquals('user@example.com', Email::sanitizeEmail('user@example.com'));
    }

    public function test_email_sanitize_accepts_email_with_subdomain(): void
    {
        $this->assertEquals('user@mail.example.com', Email::sanitizeEmail('user@mail.example.com'));
    }

    public function test_email_sanitize_accepts_email_with_plus_sign(): void
    {
        $this->assertEquals('user+tag@example.com', Email::sanitizeEmail('user+tag@example.com'));
    }

    public function test_email_sanitize_accepts_email_with_numbers(): void
    {
        $this->assertEquals('user123@example456.com', Email::sanitizeEmail('user123@example456.com'));
    }

    public function test_email_sanitize_accepts_email_with_hyphen(): void
    {
        $this->assertEquals('user-name@ex-ample.com', Email::sanitizeEmail('user-name@ex-ample.com'));
    }

    public function test_email_sanitize_accepts_email_with_underscore(): void
    {
        $this->assertEquals('user_name@example.com', Email::sanitizeEmail('user_name@example.com'));
    }

    public function test_email_sanitize_returns_false_for_only_at_symbol(): void
    {
        $this->assertFalse(Email::sanitizeEmail('@'));
    }

    public function test_email_sanitize_returns_false_for_at_at_start(): void
    {
        $this->assertFalse(Email::sanitizeEmail('@example.com'));
    }

    public function test_email_sanitize_returns_false_for_at_at_end(): void
    {
        $this->assertFalse(Email::sanitizeEmail('user@'));
    }

    public function test_email_sanitize_handles_unicode_characters(): void
    {
        $result = Email::sanitizeEmail('Üser@example.com');

        $this->assertIsString($result);
        $this->assertStringContainsString('@example.com', $result);
    }

    // -------------------------------------------------------------------------
    // Attribute casting — integer casts only (datetime casts need getConnection())
    // -------------------------------------------------------------------------

    public function test_email_casts_integer_attributes_correctly(): void
    {
        $email = new Email;
        $email->setRawAttributes([
            'type'        => '1',
            'customer_id' => '42',
        ]);

        $this->assertIsInt($email->type);
        $this->assertIsInt($email->customer_id);
    }

    // -------------------------------------------------------------------------
    // Fillable list
    // -------------------------------------------------------------------------

    public function test_email_fillable_includes_expected_fields(): void
    {
        $fillable = (new Email)->getFillable();

        foreach (['customer_id', 'email', 'type'] as $field) {
            $this->assertContains($field, $fillable);
        }
    }

    // -------------------------------------------------------------------------
    // Basic attribute assignment
    // -------------------------------------------------------------------------

    public function test_email_model_has_email_attribute(): void
    {
        $email = new Email(['email' => 'test@example.com']);

        $this->assertEquals('test@example.com', $email->email);
    }

    public function test_email_model_has_customer_id_attribute(): void
    {
        $email = new Email(['customer_id' => 123]);

        $this->assertEquals(123, $email->customer_id);
    }

    public function test_email_model_can_set_all_attributes(): void
    {
        $email = new Email([
            'email'       => 'work@example.com',
            'customer_id' => 456,
        ]);

        $this->assertEquals('work@example.com', $email->email);
        $this->assertEquals(456, $email->customer_id);
    }
}
