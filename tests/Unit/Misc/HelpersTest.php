<?php

declare(strict_types=1);

namespace Tests\Unit\Misc;

use App\Misc\Helper;
use App\Misc\MailHelper;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Tests\PureUnitTestCase;

/**
 * Wave 7 migration: unique Helper/MailHelper tests not covered by HelperLogicTest.
 * Covers: MailHelper::getMessageIdHash, Guzzle value assertions, reflection, instantiation.
 */
class HelpersTest extends PureUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstances();

        $container = new Container;
        $container->instance('config', new ConfigRepository([
            'app' => [
                'key' => 'base64:'.base64_encode('unit-test-app-key-32-chars!!!!!'),
            ],
        ]));

        Container::setInstance($container);
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Container::setInstance(null);
        parent::tearDown();
    }

    // ==================== MailHelper::getMessageIdHash ====================

    public function test_mail_helper_get_message_id_hash_generates_hash(): void
    {
        $hash = MailHelper::getMessageIdHash(123);

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);
    }

    public function test_mail_helper_get_message_id_hash_is_consistent(): void
    {
        $hash1 = MailHelper::getMessageIdHash(456);
        $hash2 = MailHelper::getMessageIdHash(456);

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

    public function test_mail_helper_hash_is_alphanumeric(): void
    {
        $hash = MailHelper::getMessageIdHash(123);

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $hash);
    }

    public function test_mail_helper_hash_has_reasonable_length(): void
    {
        $hash = MailHelper::getMessageIdHash(123);

        $this->assertGreaterThanOrEqual(8, strlen($hash));
        $this->assertLessThanOrEqual(64, strlen($hash));
    }

    // ==================== setGuzzleDefaultOptions value assertions ====================

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
            'custom_option'   => 'value',
        ]);

        $this->assertEquals(20, $options['connect_timeout']);
        $this->assertEquals('value', $options['custom_option']);
        $this->assertEquals(30, $options['timeout']); // Default unchanged
    }

    public function test_set_guzzle_default_options_preserves_custom_options(): void
    {
        $options = Helper::setGuzzleDefaultOptions([
            'headers' => ['Authorization' => 'Bearer token'],
            'proxy'   => 'http://proxy.example.com',
        ]);

        $this->assertArrayHasKey('headers', $options);
        $this->assertArrayHasKey('proxy', $options);
        $this->assertEquals('Bearer token', $options['headers']['Authorization']);
    }

    public function test_set_guzzle_options_with_null_values(): void
    {
        $options = Helper::setGuzzleDefaultOptions(['custom_option' => null]);

        $this->assertArrayHasKey('custom_option', $options);
        $this->assertNull($options['custom_option']);
    }

    public function test_guzzle_options_verify_can_be_disabled(): void
    {
        $options = Helper::setGuzzleDefaultOptions(['verify' => false]);

        $this->assertFalse($options['verify']);
    }

    public function test_guzzle_options_http_errors_can_be_enabled(): void
    {
        $options = Helper::setGuzzleDefaultOptions(['http_errors' => true]);

        $this->assertTrue($options['http_errors']);
    }

    // ==================== Reflection: all public methods are static ====================

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

    // ==================== Instantiation ====================

    public function test_helper_class_can_be_instantiated(): void
    {
        $helper = new Helper;

        $this->assertInstanceOf(Helper::class, $helper);
    }

    public function test_mail_helper_class_can_be_instantiated(): void
    {
        $mailHelper = new MailHelper;

        $this->assertInstanceOf(MailHelper::class, $mailHelper);
    }

    // ==================== queueWorkerRestart: method contract ====================

    public function test_queue_worker_restart_method_exists(): void
    {
        $this->assertTrue(
            method_exists(Helper::class, 'queueWorkerRestart'),
            'Helper::queueWorkerRestart() must be defined.'
        );
    }
}
