<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Email;
use Tests\PureUnitTestCase;

final class TestEmailHelper extends Email
{
    protected function casts(): array
    {
        return [];
    }
}

class EmailModelTest extends PureUnitTestCase
{
    private function email(int $type): TestEmailHelper
    {
        $e = new TestEmailHelper;
        $e->type = $type;

        return $e;
    }

    // -------------------------------------------------------------------------
    // isPrimary / isSecondary
    // -------------------------------------------------------------------------

    public function test_is_primary_returns_true_for_type_1(): void
    {
        $this->assertTrue($this->email(1)->isPrimary());
    }

    public function test_is_primary_returns_false_for_other_types(): void
    {
        $this->assertFalse($this->email(2)->isPrimary());
        $this->assertFalse($this->email(3)->isPrimary());
    }

    public function test_is_secondary_returns_true_for_type_2(): void
    {
        $this->assertTrue($this->email(2)->isSecondary());
    }

    public function test_is_secondary_returns_false_for_other_types(): void
    {
        $this->assertFalse($this->email(1)->isSecondary());
        $this->assertFalse($this->email(3)->isSecondary());
    }

    // -------------------------------------------------------------------------
    // sanitizeEmail — all branches
    // -------------------------------------------------------------------------

    public function test_sanitize_email_returns_lowercased_valid_address(): void
    {
        $this->assertSame('user@example.com', Email::sanitizeEmail('User@Example.COM'));
    }

    public function test_sanitize_email_returns_false_for_string_without_at(): void
    {
        $this->assertFalse(Email::sanitizeEmail('notanemail'));
    }

    public function test_sanitize_email_returns_false_for_null(): void
    {
        $this->assertFalse(Email::sanitizeEmail(null));
    }

    public function test_sanitize_email_removes_trailing_dots(): void
    {
        $result = Email::sanitizeEmail('user..@example.com');
        $this->assertNotFalse($result);
        $this->assertIsString($result);
        $this->assertStringNotContainsString('..@', $result);
    }

    public function test_sanitize_email_removes_dot_before_at(): void
    {
        $result = Email::sanitizeEmail('user.@example.com');
        $this->assertSame('user@example.com', $result);
    }

    public function test_sanitize_email_returns_false_for_empty_string(): void
    {
        $this->assertFalse(Email::sanitizeEmail(''));
    }
}
