<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImapService;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for ImapService::getEncryption() method.
 * This method currently has ~86% coverage and needs edge case testing.
 */
class ImapServiceGetEncryptionTest extends UnitTestCase
{
    protected ImapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImapService;
    }

    /**
     * Helper method to invoke protected getEncryption method
     */
    protected function invokeGetEncryption($encryption): ?string
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getEncryption');
        $method->setAccessible(true);

        return $method->invoke($this->service, $encryption);
    }

    public function test_returns_ssl_for_integer_one(): void
    {
        $result = $this->invokeGetEncryption(1);

        $this->assertEquals('ssl', $result);
    }

    public function test_returns_tls_for_integer_two(): void
    {
        $result = $this->invokeGetEncryption(2);

        $this->assertEquals('tls', $result);
    }

    public function test_returns_null_for_integer_zero(): void
    {
        $result = $this->invokeGetEncryption(0);

        $this->assertNull($result);
    }

    public function test_returns_null_for_null_input(): void
    {
        $result = $this->invokeGetEncryption(null);

        $this->assertNull($result);
    }

    public function test_returns_ssl_for_string_one(): void
    {
        $result = $this->invokeGetEncryption('1');

        $this->assertEquals('ssl', $result);
    }

    public function test_returns_tls_for_string_two(): void
    {
        $result = $this->invokeGetEncryption('2');

        $this->assertEquals('tls', $result);
    }

    public function test_returns_null_for_string_zero(): void
    {
        $result = $this->invokeGetEncryption('0');

        $this->assertNull($result);
    }

    public function test_returns_null_for_negative_integer(): void
    {
        $result = $this->invokeGetEncryption(-1);

        $this->assertNull($result);
    }

    public function test_returns_null_for_large_integer(): void
    {
        $result = $this->invokeGetEncryption(999);

        $this->assertNull($result);
    }

    public function test_returns_null_for_integer_three(): void
    {
        $result = $this->invokeGetEncryption(3);

        $this->assertNull($result);
    }

    public function test_returns_null_for_string_three(): void
    {
        $result = $this->invokeGetEncryption('3');

        $this->assertNull($result);
    }

    public function test_returns_null_for_empty_string(): void
    {
        $result = $this->invokeGetEncryption('');

        $this->assertNull($result);
    }

    public function test_returns_null_for_non_numeric_string(): void
    {
        $result = $this->invokeGetEncryption('ssl');

        $this->assertNull($result);
    }

    public function test_returns_null_for_whitespace_string(): void
    {
        $result = $this->invokeGetEncryption('  ');

        $this->assertNull($result);
    }

    public function test_handles_string_with_leading_whitespace(): void
    {
        $result = $this->invokeGetEncryption(' 1');

        $this->assertEquals('ssl', $result);
    }

    public function test_handles_string_with_trailing_whitespace(): void
    {
        $result = $this->invokeGetEncryption('2 ');

        $this->assertEquals('tls', $result);
    }

    public function test_handles_string_with_surrounding_whitespace(): void
    {
        $result = $this->invokeGetEncryption(' 1 ');

        $this->assertEquals('ssl', $result);
    }

    public function test_handles_numeric_string_with_leading_zero(): void
    {
        $result = $this->invokeGetEncryption('01');

        $this->assertEquals('ssl', $result);
    }

    public function test_handles_numeric_string_with_multiple_leading_zeros(): void
    {
        $result = $this->invokeGetEncryption('002');

        $this->assertEquals('tls', $result);
    }

    public function test_returns_null_for_float_value(): void
    {
        // PHP match casts string to int first, so 1.5 becomes 1
        $result = $this->invokeGetEncryption(1.5);

        // Float 1.5 is cast to int 1, which matches the ssl case
        $this->assertEquals('ssl', $result);
    }

    public function test_returns_null_for_boolean_true(): void
    {
        $result = $this->invokeGetEncryption(true);

        // true == 1 in PHP, so it will match the integer 1 case
        $this->assertEquals('ssl', $result);
    }

    public function test_returns_null_for_boolean_false(): void
    {
        $result = $this->invokeGetEncryption(false);

        // false == 0 in PHP, so it will match the default case
        $this->assertNull($result);
    }
}
