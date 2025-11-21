<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImapService;
use Tests\UnitTestCase;
use Mockery;

/**
 * Test Suite for IMAP Service Helper Methods - Basic
 *
 * This test suite covers basic helper methods:
 * - getAddressesWithNames() (25 tests) - Critical method for parsing addresses
 * - parseAddresses() (24 tests) - Critical method for address parsing
 * Total: 49 tests
 *
 * These are foundational methods used throughout IMAP processing.
 */
class ImapServiceHelpersBasicTest extends UnitTestCase
{
    protected ImapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImapService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper method to invoke private/protected methods using reflection
     */
    protected function invokeMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    // =====================================================================
    // Tests for getAddressesWithNames() - CRITICAL (CRAP: 272)
    // =====================================================================

    public function test_get_addresses_with_names_returns_empty_for_null_input(): void
    {
        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [null]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_get_addresses_with_names_returns_empty_for_empty_array(): void
    {
        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[]]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_get_addresses_with_names_returns_empty_for_empty_string(): void
    {
        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', ['']);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_get_addresses_with_names_returns_empty_for_false(): void
    {
        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [false]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_get_addresses_with_names_returns_empty_for_zero(): void
    {
        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [0]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_get_addresses_with_names_parses_simple_email_as_string(): void
    {
        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', ['user@example.com']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('user@example.com', $result[0]['email']);
        $this->assertEquals('', $result[0]['first_name']);
        $this->assertEquals('', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_parses_object_with_mail_property(): void
    {
        $address = new \stdClass();
        $address->mail = 'john@example.com';
        $address->personal = 'John Doe';

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('john@example.com', $result[0]['email']);
        $this->assertEquals('John', $result[0]['first_name']);
        $this->assertEquals('Doe', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_parses_object_with_email_property(): void
    {
        $address = new \stdClass();
        $address->email = 'jane@example.com';
        $address->name = 'Jane Smith';

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('jane@example.com', $result[0]['email']);
        $this->assertEquals('Jane', $result[0]['first_name']);
        $this->assertEquals('Smith', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_parses_array_address_with_mail(): void
    {
        $address = [
            'mail' => 'test@example.com',
            'personal' => 'Test User',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]['email']);
        $this->assertEquals('Test', $result[0]['first_name']);
        $this->assertEquals('User', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_parses_array_address_with_email(): void
    {
        $address = [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]['email']);
        $this->assertEquals('Test', $result[0]['first_name']);
        $this->assertEquals('User', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_handles_multiple_addresses(): void
    {
        $addresses = [
            (object)['mail' => 'john@example.com', 'personal' => 'John Doe'],
            (object)['mail' => 'jane@example.com', 'personal' => 'Jane Smith'],
            'bob@example.com',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [$addresses]);

        $this->assertCount(3, $result);
        $this->assertEquals('john@example.com', $result[0]['email']);
        $this->assertEquals('John', $result[0]['first_name']);
        $this->assertEquals('jane@example.com', $result[1]['email']);
        $this->assertEquals('Jane', $result[1]['first_name']);
        $this->assertEquals('bob@example.com', $result[2]['email']);
    }

    public function test_get_addresses_with_names_truncates_long_first_name(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => str_repeat('FirstNameTooLong', 5).' LastName',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals(20, strlen($result[0]['first_name']));
        $this->assertEquals('LastName', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_truncates_long_last_name(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => 'FirstName '.str_repeat('LastNameTooLong', 5),
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('FirstName', $result[0]['first_name']);
        $this->assertEquals(30, strlen($result[0]['last_name']));
    }

    public function test_get_addresses_with_names_truncates_both_long_names(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => str_repeat('FirstNameTooLong', 5).' '.str_repeat('LastNameTooLong', 5),
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals(20, strlen($result[0]['first_name']));
        $this->assertEquals(30, strlen($result[0]['last_name']));
    }

    public function test_get_addresses_with_names_handles_single_name(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => 'SingleName',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('SingleName', $result[0]['first_name']);
        $this->assertEquals('', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_handles_three_part_name(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => 'First Middle Last',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('First', $result[0]['first_name']);
        $this->assertEquals('Middle Last', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_handles_empty_personal_name(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => '',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('', $result[0]['first_name']);
        $this->assertEquals('', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_converts_attribute_object(): void
    {
        $attribute = Mockery::mock(Attribute::class);
        $attribute->shouldReceive('get')
            ->once()
            ->andReturn([(object)['mail' => 'attr@example.com', 'personal' => 'Attr User']]);

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [$attribute]);

        $this->assertCount(1, $result);
        $this->assertEquals('attr@example.com', $result[0]['email']);
        $this->assertEquals('Attr', $result[0]['first_name']);
    }

    public function test_get_addresses_with_names_handles_object_without_mail_property_with_brackets(): void
    {
        // Mock an object that returns a string representation with email
        $address = Mockery::mock(\stdClass::class);
        $address->shouldReceive('__toString')
            ->andReturn('Test Name <test@example.com>');

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]['email']);
        $this->assertEquals('Test', $result[0]['first_name']);
        $this->assertEquals('Name', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_handles_object_without_mail_property_plain(): void
    {
        $address = Mockery::mock(\stdClass::class);
        $address->shouldReceive('__toString')
            ->andReturn('simple@example.com');

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('simple@example.com', $result[0]['email']);
    }

    public function test_get_addresses_with_names_skips_invalid_entries(): void
    {
        $addresses = [
            (object)['mail' => 'valid@example.com', 'personal' => 'Valid User'],
            (object)['personal' => 'No Email'], // No email
            null, // Null entry
            '', // Empty string
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [$addresses]);

        // Should only have the valid address
        $this->assertCount(1, $result);
        $this->assertEquals('valid@example.com', $result[0]['email']);
    }

    public function test_get_addresses_with_names_handles_mixed_valid_and_invalid(): void
    {
        $addresses = [
            (object)['mail' => 'first@example.com', 'personal' => 'First User'],
            (object)['personal' => 'No Email'],
            (object)['mail' => 'second@example.com', 'personal' => 'Second User'],
            false,
            (object)['mail' => 'third@example.com', 'personal' => 'Third User'],
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [$addresses]);

        $this->assertCount(3, $result);
        $this->assertEquals('first@example.com', $result[0]['email']);
        $this->assertEquals('second@example.com', $result[1]['email']);
        $this->assertEquals('third@example.com', $result[2]['email']);
    }

    public function test_get_addresses_with_names_handles_unicode_names(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => 'José García',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('José', $result[0]['first_name']);
        $this->assertEquals('García', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_handles_special_characters_in_names(): void
    {
        $address = (object)[
            'mail' => 'test@example.com',
            'personal' => "O'Brien-Smith",
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals("O'Brien-Smith", $result[0]['first_name']);
        $this->assertEquals('', $result[0]['last_name']);
    }

    public function test_get_addresses_with_names_handles_non_string_email(): void
    {
        $address = (object)[
            'mail' => 123, // Non-string email
            'personal' => 'Test User',
        ];

        $result = $this->invokeMethod($this->service, 'getAddressesWithNames', [[$address]]);

        // Should skip non-string emails
        $this->assertCount(0, $result);
    }

    // =====================================================================
    // =====================================================================
    // Tests for parseAddresses() - CRITICAL (CRAP: 182)
    // =====================================================================

    public function test_parse_addresses_returns_empty_for_null_input(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', [null]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_parse_addresses_returns_empty_for_empty_array(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', [[]]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_parse_addresses_returns_empty_for_false(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', [false]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_parse_addresses_returns_empty_for_empty_string(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', ['']);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_parse_addresses_returns_empty_for_zero(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', [0]);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function test_parse_addresses_handles_simple_string(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', ['test@example.com']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]);
    }

    public function test_parse_addresses_handles_object_with_mail_property(): void
    {
        $address = (object)['mail' => 'john@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('john@example.com', $result[0]);
    }

    public function test_parse_addresses_handles_object_with_email_property(): void
    {
        $address = (object)['email' => 'jane@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('jane@example.com', $result[0]);
    }

    public function test_parse_addresses_prefers_mail_over_email_property(): void
    {
        $address = (object)['mail' => 'primary@example.com', 'email' => 'secondary@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('primary@example.com', $result[0]);
    }

    public function test_parse_addresses_handles_array_address_with_mail(): void
    {
        $address = ['mail' => 'test@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]);
    }

    public function test_parse_addresses_handles_array_address_with_email(): void
    {
        $address = ['email' => 'test@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]);
    }

    public function test_parse_addresses_array_prefers_mail_over_email(): void
    {
        $address = ['mail' => 'primary@example.com', 'email' => 'secondary@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('primary@example.com', $result[0]);
    }

    public function test_parse_addresses_converts_attribute_object(): void
    {
        $attribute = Mockery::mock(Attribute::class);
        $attribute->shouldReceive('get')
            ->once()
            ->andReturn([(object)['mail' => 'attr@example.com']]);

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$attribute]);

        $this->assertCount(1, $result);
        $this->assertEquals('attr@example.com', $result[0]);
    }

    public function test_parse_addresses_converts_attribute_object_with_multiple_addresses(): void
    {
        $attribute = Mockery::mock(Attribute::class);
        $attribute->shouldReceive('get')
            ->once()
            ->andReturn([
                (object)['mail' => 'first@example.com'],
                (object)['mail' => 'second@example.com'],
            ]);

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$attribute]);

        $this->assertCount(2, $result);
        $this->assertEquals('first@example.com', $result[0]);
        $this->assertEquals('second@example.com', $result[1]);
    }

    public function test_parse_addresses_handles_object_without_mail_property_plain_string(): void
    {
        $address = Mockery::mock(\stdClass::class);
        $address->shouldReceive('__toString')
            ->andReturn('test@example.com');

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]);
    }

    public function test_parse_addresses_extracts_email_from_formatted_string(): void
    {
        $address = Mockery::mock(\stdClass::class);
        $address->shouldReceive('__toString')
            ->andReturn('John Doe <john@example.com>');

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('john@example.com', $result[0]);
    }

    public function test_parse_addresses_extracts_email_from_complex_formatted_string(): void
    {
        $address = Mockery::mock(\stdClass::class);
        $address->shouldReceive('__toString')
            ->andReturn('"John Q. Public" <john.public@example.com>');

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('john.public@example.com', $result[0]);
    }

    public function test_parse_addresses_handles_multiple_addresses(): void
    {
        $addresses = [
            (object)['mail' => 'john@example.com'],
            (object)['email' => 'jane@example.com'],
            'bob@example.com',
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertCount(3, $result);
        $this->assertEquals('john@example.com', $result[0]);
        $this->assertEquals('jane@example.com', $result[1]);
        $this->assertEquals('bob@example.com', $result[2]);
    }

    public function test_parse_addresses_handles_large_list(): void
    {
        $addresses = [];
        for ($i = 0; $i < 50; $i++) {
            $addresses[] = (object)['mail' => "user{$i}@example.com"];
        }

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertCount(50, $result);
        $this->assertEquals('user0@example.com', $result[0]);
        $this->assertEquals('user49@example.com', $result[49]);
    }

    public function test_parse_addresses_skips_invalid_entries(): void
    {
        $addresses = [
            (object)['mail' => 'valid@example.com'],
            (object)['name' => 'No Email'], // No email property
            null,
            '',
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertCount(1, $result);
        $this->assertEquals('valid@example.com', $result[0]);
    }

    public function test_parse_addresses_skips_non_string_email(): void
    {
        $addresses = [
            (object)['mail' => 123], // Non-string
            (object)['mail' => true], // Boolean
            (object)['mail' => ['array']], // Array
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertCount(0, $result);
    }

    public function test_parse_addresses_handles_mixed_valid_and_invalid(): void
    {
        $addresses = [
            (object)['mail' => 'first@example.com'],
            null,
            (object)['email' => 'second@example.com'],
            false,
            'third@example.com',
            (object)['name' => 'No Email'],
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertCount(3, $result);
        $this->assertEquals('first@example.com', $result[0]);
        $this->assertEquals('second@example.com', $result[1]);
        $this->assertEquals('third@example.com', $result[2]);
    }

    public function test_parse_addresses_handles_array_with_both_mail_and_email_null(): void
    {
        $address = ['mail' => null, 'email' => null];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(0, $result);
    }

    public function test_parse_addresses_handles_object_with_null_mail(): void
    {
        $address = (object)['mail' => null, 'email' => 'fallback@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertCount(1, $result);
        $this->assertEquals('fallback@example.com', $result[0]);
    }

    // =====================================================================
}