<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImapService;
use Tests\UnitTestCase;

/**
 * Test ImapService::parseAddresses() method
 *
 * CRAP Score: 420 (Critical Priority)
 * Target Coverage: 90%+
 */
class ImapServiceParseAddressesTest extends UnitTestCase
{
    private ImapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImapService;
    }

    public function test_parse_addresses_with_empty_input_returns_empty_array(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', [null]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_parse_addresses_with_empty_string_returns_empty_array(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', ['']);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_parse_addresses_with_empty_array_returns_empty_array(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', [[]]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_parse_addresses_with_simple_string_email_returns_array(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', ['john@example.com']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('john@example.com', $result[0]);
    }

    public function test_parse_addresses_with_email_and_name_string_returns_full_string(): void
    {
        // Plain strings are returned as-is (no extraction from angle brackets)
        $result = $this->invokeMethod($this->service, 'parseAddresses', ['John Doe <john@example.com>']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('John Doe <john@example.com>', $result[0]);
    }

    public function test_parse_addresses_with_quoted_name_returns_full_string(): void
    {
        // Plain strings are returned as-is (no extraction from angle brackets)
        $result = $this->invokeMethod($this->service, 'parseAddresses', ['"Doe, John" <john@example.com>']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('"Doe, John" <john@example.com>', $result[0]);
    }

    public function test_parse_addresses_with_array_of_strings_returns_all(): void
    {
        $addresses = [
            'john@example.com',
            'jane@example.com',
            'bob@example.com',
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals($addresses, $result);
    }

    public function test_parse_addresses_with_mixed_format_array_returns_all_as_is(): void
    {
        // Plain strings are NOT parsed - returned as-is
        $addresses = [
            'john@example.com',
            'Jane Doe <jane@example.com>',
            '"Smith, Bob" <bob@example.com>',
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals('john@example.com', $result[0]);
        $this->assertEquals('Jane Doe <jane@example.com>', $result[1]);
        $this->assertEquals('"Smith, Bob" <bob@example.com>', $result[2]);
    }

    public function test_parse_addresses_with_object_having_mail_property_returns_email(): void
    {
        $address = (object) ['mail' => 'test@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]);
    }

    public function test_parse_addresses_with_object_having_email_property_returns_email(): void
    {
        $address = (object) ['email' => 'test@example.com'];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]);
    }

    public function test_parse_addresses_with_array_having_mail_key_returns_email(): void
    {
        $addresses = [
            ['mail' => 'test1@example.com'],
            ['mail' => 'test2@example.com'],
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('test1@example.com', $result[0]);
        $this->assertEquals('test2@example.com', $result[1]);
    }

    public function test_parse_addresses_with_array_having_email_key_returns_email(): void
    {
        $addresses = [
            ['email' => 'test1@example.com'],
            ['email' => 'test2@example.com'],
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('test1@example.com', $result[0]);
        $this->assertEquals('test2@example.com', $result[1]);
    }

    public function test_parse_addresses_skips_null_entries_in_array(): void
    {
        $addresses = [
            'john@example.com',
            null,
            'jane@example.com',
            null,
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('john@example.com', $result[0]);
        $this->assertEquals('jane@example.com', $result[1]);
    }

    public function test_parse_addresses_skips_empty_string_entries(): void
    {
        $addresses = [
            'john@example.com',
            '',
            'jane@example.com',
            '   ',
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('john@example.com', $result[0]);
        $this->assertEquals('jane@example.com', $result[1]);
    }

    public function test_parse_addresses_with_unicode_characters_preserves_them(): void
    {
        // Plain strings are returned as-is, including unicode
        $result = $this->invokeMethod($this->service, 'parseAddresses', ['山田太郎 <yamada@example.jp>']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('山田太郎 <yamada@example.jp>', $result[0]);
    }

    public function test_parse_addresses_with_plus_addressing_preserves_it(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', ['user+tag@example.com']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('user+tag@example.com', $result[0]);
    }

    public function test_parse_addresses_with_subdomain_email_works(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', ['user@mail.example.com']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('user@mail.example.com', $result[0]);
    }

    public function test_parse_addresses_with_multiple_angle_brackets_returns_as_is(): void
    {
        // Plain strings are not parsed - returned as-is
        $result = $this->invokeMethod($this->service, 'parseAddresses', ['<<test@example.com>>']);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('<<test@example.com>>', $result[0]);
    }

    public function test_parse_addresses_with_object_to_string_method_parses(): void
    {
        $address = new class
        {
            public function __toString(): string
            {
                return 'Test User <test@example.com>';
            }
        };

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]);
    }

    public function test_parse_addresses_with_object_to_string_without_brackets(): void
    {
        $address = new class
        {
            public function __toString(): string
            {
                return 'test@example.com';
            }
        };

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]);
    }

    public function test_parse_addresses_with_object_get_method_calls_it(): void
    {
        $attribute = new class
        {
            public function get(): array
            {
                return ['test1@example.com', 'test2@example.com'];
            }
        };

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$attribute]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('test1@example.com', $result[0]);
        $this->assertEquals('test2@example.com', $result[1]);
    }

    public function test_parse_addresses_with_non_array_non_string_returns_empty(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', [12345]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_parse_addresses_with_boolean_returns_empty(): void
    {
        $result = $this->invokeMethod($this->service, 'parseAddresses', [true]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_parse_addresses_with_object_having_both_mail_and_email_prefers_mail(): void
    {
        $address = (object) [
            'mail' => 'mail-property@example.com',
            'email' => 'email-property@example.com',
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('mail-property@example.com', $result[0]);
    }

    public function test_parse_addresses_with_object_to_string_extracts_from_angle_brackets(): void
    {
        // Objects with __toString() DO extract from angle brackets
        $address = new class
        {
            public function __toString(): string
            {
                return 'Test User <extracted@example.com>';
            }
        };

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('extracted@example.com', $result[0]);
    }

    public function test_parse_addresses_with_object_no_properties_no_to_string_skips(): void
    {
        $address = new class {};

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_parse_addresses_with_array_having_both_keys_prefers_mail(): void
    {
        $addresses = [
            [
                'mail' => 'mail-key@example.com',
                'email' => 'email-key@example.com',
            ],
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('mail-key@example.com', $result[0]);
    }

    public function test_parse_addresses_with_array_having_empty_mail_skips(): void
    {
        $addresses = [
            ['mail' => ''],
            ['mail' => '   '],
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_parse_addresses_with_mixed_objects_arrays_strings(): void
    {
        $addresses = [
            'plain@example.com',
            (object) ['mail' => 'object@example.com'],
            ['email' => 'array@example.com'],
            new class
            {
                public function __toString(): string
                {
                    return 'ToString <tostring@example.com>';
                }
            },
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertIsArray($result);
        $this->assertCount(4, $result);
        $this->assertEquals('plain@example.com', $result[0]);
        $this->assertEquals('object@example.com', $result[1]);
        $this->assertEquals('array@example.com', $result[2]);
        $this->assertEquals('tostring@example.com', $result[3]);
    }

    public function test_parse_addresses_with_object_mail_property_null_tries_email(): void
    {
        $address = (object) [
            'mail' => null,
            'email' => 'fallback@example.com',
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('fallback@example.com', $result[0]);
    }

    public function test_parse_addresses_with_array_mail_key_null_tries_email(): void
    {
        $addresses = [
            [
                'mail' => null,
                'email' => 'fallback@example.com',
            ],
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('fallback@example.com', $result[0]);
    }

    public function test_parse_addresses_with_object_to_string_no_brackets_returns_full(): void
    {
        $address = new class
        {
            public function __toString(): string
            {
                return 'noBrackets@example.com';
            }
        };

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('noBrackets@example.com', $result[0]);
    }

    public function test_parse_addresses_with_object_to_string_empty_skips(): void
    {
        $address = new class
        {
            public function __toString(): string
            {
                return '';
            }
        };

        $result = $this->invokeMethod($this->service, 'parseAddresses', [[$address]]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_parse_addresses_with_get_method_returning_complex_array(): void
    {
        $attribute = new class
        {
            public function get(): array
            {
                return [
                    'simple@example.com',
                    (object) ['mail' => 'object@example.com'],
                    ['email' => 'array@example.com'],
                ];
            }
        };

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$attribute]);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals('simple@example.com', $result[0]);
        $this->assertEquals('object@example.com', $result[1]);
        $this->assertEquals('array@example.com', $result[2]);
    }

    public function test_parse_addresses_handles_large_array(): void
    {
        $addresses = array_map(fn ($i) => "user{$i}@example.com", range(1, 100));

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertIsArray($result);
        $this->assertCount(100, $result);
        $this->assertEquals('user1@example.com', $result[0]);
        $this->assertEquals('user100@example.com', $result[99]);
    }

    public function test_parse_addresses_with_nested_array_not_supported(): void
    {
        // Nested arrays are treated as invalid entries (not recursively processed)
        $addresses = [
            'valid@example.com',
            ['nested@example.com'], // This is an array, not ['mail' => ...] format
        ];

        $result = $this->invokeMethod($this->service, 'parseAddresses', [$addresses]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result); // Only the first valid entry
        $this->assertEquals('valid@example.com', $result[0]);
    }

    /**
     * Helper to invoke protected/private methods
     */
    private function invokeMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
