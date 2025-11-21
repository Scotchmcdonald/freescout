<?php

declare(strict_types=1);

namespace Tests\Unit\Misc;

use App\Misc\Helper;
use App\Misc\MailHelper;
use Illuminate\Support\Facades\Artisan;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for Helper classes
 * Following TESTING_GUIDE.md standards
 */
class HelpersTest extends UnitTestCase
{
    // ==================== Helper Class Tests ====================

    public function test_is_installed_returns_true_when_app_key_exists(): void
    {
        config(['app.key' => 'base64:'.base64_encode('test-key-32-characters-long!!')]);

        $result = Helper::isInstalled();

        $this->assertTrue($result);
    }

    public function test_is_installed_returns_false_when_app_key_is_null(): void
    {
        config(['app.key' => null]);

        $result = Helper::isInstalled();

        $this->assertFalse($result);
    }

    public function test_is_installed_returns_false_when_app_key_is_empty(): void
    {
        config(['app.key' => '']);

        $result = Helper::isInstalled();

        $this->assertFalse($result);
    }

    public function test_queue_worker_restart_calls_artisan(): void
    {
        Artisan::shouldReceive('call')
            ->once()
            ->with('queue:restart')
            ->andReturn(0);

        Helper::queueWorkerRestart();

        $this->expectNotToPerformAssertions(); // Assertion for mock expectation
    }

    public function test_dir_permissions_constant_is_defined(): void
    {
        $this->assertEquals(0755, Helper::DIR_PERMISSIONS);
    }

    public function test_set_guzzle_default_options_returns_default_options(): void
    {
        $options = Helper::setGuzzleDefaultOptions();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('http_errors', $options);
        $this->assertArrayHasKey('connect_timeout', $options);
        $this->assertArrayHasKey('timeout', $options);
        $this->assertArrayHasKey('verify', $options);
    }

    public function test_set_guzzle_default_options_has_correct_values(): void
    {
        $options = Helper::setGuzzleDefaultOptions();

        $this->assertFalse($options['http_errors']);
        $this->assertEquals(10, $options['connect_timeout']);
        $this->assertEquals(30, $options['timeout']);
        $this->assertTrue($options['verify']);
    }

    public function test_set_guzzle_default_options_can_be_overridden(): void
    {
        $options = Helper::setGuzzleDefaultOptions([
            'connect_timeout' => 20,
            'custom_option' => 'value',
        ]);

        $this->assertEquals(20, $options['connect_timeout']);
        $this->assertEquals('value', $options['custom_option']);
        $this->assertEquals(30, $options['timeout']); // Default remains
    }

    public function test_set_guzzle_default_options_preserves_custom_options(): void
    {
        $customOptions = [
            'headers' => ['Authorization' => 'Bearer token'],
            'proxy' => 'http://proxy.example.com',
        ];

        $options = Helper::setGuzzleDefaultOptions($customOptions);

        $this->assertArrayHasKey('headers', $options);
        $this->assertArrayHasKey('proxy', $options);
        $this->assertEquals('Bearer token', $options['headers']['Authorization']);
    }

    // ==================== MailHelper Class Tests ====================

    public function test_mail_helper_get_message_id_hash_generates_hash(): void
    {
        $threadId = 123;

        $hash = MailHelper::getMessageIdHash($threadId);

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
    }

    public function test_mail_helper_get_message_id_hash_is_consistent(): void
    {
        $threadId = 456;

        $hash1 = MailHelper::getMessageIdHash($threadId);
        $hash2 = MailHelper::getMessageIdHash($threadId);

        $this->assertEquals($hash1, $hash2);
    }

    public function test_mail_helper_get_message_id_hash_differs_for_different_threads(): void
    {
        $hash1 = MailHelper::getMessageIdHash(100);
        $hash2 = MailHelper::getMessageIdHash(200);

        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_mail_helper_get_message_id_hash_handles_zero(): void
    {
        $hash = MailHelper::getMessageIdHash(0);

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
    }

    public function test_mail_helper_get_message_id_hash_handles_negative_numbers(): void
    {
        $hash = MailHelper::getMessageIdHash(-1);

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
    }

    public function test_mail_helper_get_message_id_hash_handles_large_numbers(): void
    {
        $hash = MailHelper::getMessageIdHash(999999999);

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
    }

    // ==================== Edge Cases ====================

    public function test_helper_methods_are_static(): void
    {
        $reflection = new \ReflectionClass(Helper::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->name !== '__construct') {
                $this->assertTrue($method->isStatic(), "Method {$method->name} should be static");
            }
        }
    }

    public function test_mail_helper_methods_are_static(): void
    {
        $reflection = new \ReflectionClass(MailHelper::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->name !== '__construct') {
                $this->assertTrue($method->isStatic(), "Method {$method->name} should be static");
            }
        }
    }

    public function test_set_guzzle_options_with_empty_array(): void
    {
        $options = Helper::setGuzzleDefaultOptions([]);

        $this->assertArrayHasKey('http_errors', $options);
        $this->assertArrayHasKey('connect_timeout', $options);
        $this->assertArrayHasKey('timeout', $options);
        $this->assertArrayHasKey('verify', $options);
    }

    public function test_set_guzzle_options_with_null_values(): void
    {
        $options = Helper::setGuzzleDefaultOptions([
            'custom_option' => null,
        ]);

        $this->assertArrayHasKey('custom_option', $options);
        $this->assertNull($options['custom_option']);
    }

    public function test_helper_class_can_be_instantiated(): void
    {
        // Helper is a utility class with static methods
        // but should still be instantiatable
        $helper = new Helper;

        $this->assertInstanceOf(Helper::class, $helper);
    }

    public function test_mail_helper_class_can_be_instantiated(): void
    {
        $mailHelper = new MailHelper;

        $this->assertInstanceOf(MailHelper::class, $mailHelper);
    }

    public function test_is_installed_works_with_base64_encoded_key(): void
    {
        config(['app.key' => 'base64:c29tZS1yYW5kb20ta2V5LWZvci10ZXN0aW5n']);

        $result = Helper::isInstalled();

        $this->assertTrue($result);
    }

    public function test_guzzle_options_verify_can_be_disabled(): void
    {
        $options = Helper::setGuzzleDefaultOptions([
            'verify' => false,
        ]);

        $this->assertFalse($options['verify']);
    }

    public function test_guzzle_options_http_errors_can_be_enabled(): void
    {
        $options = Helper::setGuzzleDefaultOptions([
            'http_errors' => true,
        ]);

        $this->assertTrue($options['http_errors']);
    }

    public function test_mail_helper_hash_is_alphanumeric(): void
    {
        $hash = MailHelper::getMessageIdHash(123);

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $hash);
    }

    public function test_mail_helper_hash_has_reasonable_length(): void
    {
        $hash = MailHelper::getMessageIdHash(123);

        // Hash should be between 8 and 64 characters (typical hash lengths)
        $this->assertGreaterThanOrEqual(8, strlen($hash));
        $this->assertLessThanOrEqual(64, strlen($hash));
    }
}
