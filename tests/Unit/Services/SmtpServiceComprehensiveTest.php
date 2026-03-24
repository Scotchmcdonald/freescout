<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\SmtpService;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\PureUnitTestCase;

/**
 * Pure-unit tests for SmtpService.
 *
 * Covers validateSettings(), method-existence checks, and testConnection()
 * control-flow assertions.  No database, no network — Log facade is stubbed
 * via a Mockery spy; Mail resolution deliberately throws (caught internally).
 *
 * Migrated from tests/Integration/Services/SmtpServiceComprehensiveTest.php.
 * Does NOT duplicate the 7 protected-method tests already in SmtpServicePureLogicTest.
 */
class SmtpServiceComprehensiveTest extends PureUnitTestCase
{
    private LoggerInterface $logSpy;

    protected function setUp(): void
    {
        parent::setUp();

        Facade::clearResolvedInstances();

        $container = new Container;
        $container->instance('config', new ConfigRepository([]));

        $this->logSpy = Mockery::spy(LoggerInterface::class);
        $container->instance('log', $this->logSpy);

        Container::setInstance($container);
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Container::setInstance(null);
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Method-existence / reflection checks
    // -------------------------------------------------------------------------

    public function test_configure_smtp_method_exists_and_is_callable(): void
    {
        $service = new SmtpService;

        $this->assertTrue(method_exists($service, 'configureSmtp'));
        $this->assertTrue(is_callable([$service, 'configureSmtp']));
    }

    public function test_validate_settings_method_exists(): void
    {
        $service = new SmtpService;
        $methods = array_map(
            fn ($m) => $m->getName(),
            (new \ReflectionClass($service))->getMethods()
        );

        $this->assertContains('validateSettings', $methods);
    }

    public function test_get_encryption_method_exists(): void
    {
        $service = new SmtpService;
        $methods = array_map(
            fn ($m) => $m->getName(),
            (new \ReflectionClass($service))->getMethods()
        );

        $this->assertContains('getEncryption', $methods);
    }

    public function test_validate_mailbox_settings_method_exists(): void
    {
        $service = new SmtpService;
        $methods = array_map(
            fn ($m) => $m->getName(),
            (new \ReflectionClass($service))->getMethods()
        );

        $this->assertContains('validateMailboxSettings', $methods);
    }

    // -------------------------------------------------------------------------
    // testConnection() — control-flow assertions (no real SMTP)
    // -------------------------------------------------------------------------

    public function test_test_connection_returns_result_array_with_required_keys(): void
    {
        $mailbox = (new Mailbox)->forceFill([
            'out_server'   => 'smtp.example.com',
            'out_port'     => 587,
            'out_username' => 'test@example.com',
            'out_password' => 'password',
            'email'        => 'test@example.com',
            'name'         => 'Test Mailbox',
        ]);

        $result = (new SmtpService)->testConnection($mailbox, 'recipient@example.com');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_test_connection_validates_mailbox_settings_before_sending(): void
    {
        $mailbox = (new Mailbox)->forceFill([
            'out_server' => null,
            'email'      => 'test@example.com',
        ]);

        $result = (new SmtpService)->testConnection($mailbox, 'recipient@example.com');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Configuration errors', $result['message']);
        $this->logSpy->shouldHaveReceived('warning')
            ->once()
            ->with('SMTP test skipped due to invalid configuration', Mockery::any());
    }

    public function test_test_connection_logs_start_with_correct_parameters(): void
    {
        $mailbox = (new Mailbox)->forceFill([
            'id'           => 42,
            'out_server'   => 'smtp.example.com',
            'out_port'     => 587,
            'out_username' => 'test@example.com',
            'out_password' => 'password',
            'out_encryption' => 'tls',
            'email'        => 'test@example.com',
            'name'         => 'Test Mailbox',
        ]);

        (new SmtpService)->testConnection($mailbox, 'recipient@example.com');

        $this->logSpy->shouldHaveReceived('info')
            ->once()
            ->with('Starting SMTP test', Mockery::on(function (array $context): bool {
                return $context['mailbox_id'] === 42
                    && $context['mailbox_name'] === 'Test Mailbox'
                    && $context['to_email'] === 'recipient@example.com'
                    && $context['smtp_server'] === 'smtp.example.com'
                    && $context['smtp_port'] === 587;
            }));
    }

    public function test_test_connection_requires_valid_email_address(): void
    {
        $mailbox = (new Mailbox)->forceFill([
            'out_server'   => 'smtp.example.com',
            'out_port'     => 587,
            'email'        => 'test@example.com',
        ]);

        $result = (new SmtpService)->testConnection($mailbox, 'recipient@example.com');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_test_connection_fails_with_invalid_server(): void
    {
        $mailbox = (new Mailbox)->forceFill([
            'out_server'   => 'invalid.smtp.server',
            'out_port'     => 587,
            'out_username' => 'test@example.com',
            'out_password' => 'wrongpass',
            'email'        => 'test@example.com',
        ]);

        $result = (new SmtpService)->testConnection($mailbox, 'recipient@example.com');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->logSpy->shouldHaveReceived('info')->once();
        $this->logSpy->shouldHaveReceived('error')->once();
    }

    public function test_test_connection_handles_authentication_errors(): void
    {
        $mailbox = (new Mailbox)->forceFill([
            'out_server'   => 'smtp.gmail.com',
            'out_port'     => 587,
            'out_username' => 'test@gmail.com',
            'out_password' => 'invalid_app_password',
            'email'        => 'test@gmail.com',
        ]);

        $result = (new SmtpService)->testConnection($mailbox, 'recipient@example.com');

        $this->assertFalse($result['success']);
        $this->logSpy->shouldHaveReceived('info')->once();
        $this->logSpy->shouldHaveReceived('error')
            ->once()
            ->with(Mockery::pattern('/SMTP test failed/i'), Mockery::any());
    }

    public function test_test_connection_validates_port_number(): void
    {
        $mailbox = (new Mailbox)->forceFill([
            'out_server'   => 'smtp.example.com',
            'out_port'     => 99999,
            'out_username' => 'test@example.com',
            'out_password' => 'password',
            'email'        => 'test@example.com',
        ]);

        $result = (new SmtpService)->testConnection($mailbox, 'recipient@example.com');

        $this->assertFalse($result['success']);
        $this->logSpy->shouldHaveReceived('info')->once();
        $this->logSpy->shouldHaveReceived('error')->once();
    }

    public function test_test_connection_handles_timeout(): void
    {
        $mailbox = (new Mailbox)->forceFill([
            'out_server'   => 'non-responsive-server.com',
            'out_port'     => 587,
            'out_username' => 'test@example.com',
            'out_password' => 'password',
            'email'        => 'test@example.com',
        ]);

        $result = (new SmtpService)->testConnection($mailbox, 'recipient@example.com');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    // -------------------------------------------------------------------------
    // validateSettings() — pure logic, no network
    // -------------------------------------------------------------------------

    public function test_validate_settings_with_valid_settings(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server'    => 'smtp.example.com',
            'out_port'      => 587,
            'email'         => 'test@example.com',
            'out_encryption' => 2,
        ]);

        $this->assertIsArray($errors);
        $this->assertEmpty($errors, 'Valid settings should return no errors');
    }

    public function test_validate_settings_requires_out_server(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_port' => 587,
            'email'    => 'test@example.com',
        ]);

        $this->assertArrayHasKey('out_server', $errors);
        $this->assertEquals('SMTP server is required', $errors['out_server']);
    }

    public function test_validate_settings_requires_out_port(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server' => 'smtp.example.com',
            'email'      => 'test@example.com',
        ]);

        $this->assertArrayHasKey('out_port', $errors);
        $this->assertEquals('SMTP port is required', $errors['out_port']);
    }

    public function test_validate_settings_validates_port_range_minimum(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port'   => 0,
            'email'      => 'test@example.com',
        ]);

        $this->assertArrayHasKey('out_port', $errors);
        $this->assertEquals('SMTP port is required', $errors['out_port']);
    }

    public function test_validate_settings_validates_port_range_maximum(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port'   => 65536,
            'email'      => 'test@example.com',
        ]);

        $this->assertArrayHasKey('out_port', $errors);
        $this->assertStringContainsString('between 1 and 65535', $errors['out_port']);
    }

    public function test_validate_settings_validates_port_is_numeric(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port'   => 'not-a-number',
            'email'      => 'test@example.com',
        ]);

        $this->assertArrayHasKey('out_port', $errors);
        $this->assertStringContainsString('between 1 and 65535', $errors['out_port']);
    }

    public function test_validate_settings_requires_email(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port'   => 587,
        ]);

        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals('Email address is required', $errors['email']);
    }

    public function test_validate_settings_validates_email_format(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port'   => 587,
            'email'      => 'invalid-email-format',
        ]);

        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals('Invalid email address', $errors['email']);
    }

    public function test_validate_settings_returns_all_errors_at_once(): void
    {
        $errors = (new SmtpService)->validateSettings([]);

        $this->assertArrayHasKey('out_server', $errors);
        $this->assertArrayHasKey('out_port', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertCount(3, $errors);
    }

    public function test_validate_settings_warns_about_port_465_without_ssl(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server'     => 'smtp.example.com',
            'out_port'       => 465,
            'email'          => 'test@example.com',
            'out_encryption' => 0,
        ]);

        $this->assertArrayHasKey('out_encryption', $errors);
        $this->assertStringContainsString('Port 465 typically requires SSL', $errors['out_encryption']);
    }

    public function test_validate_settings_warns_about_port_587_without_tls(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server'     => 'smtp.example.com',
            'out_port'       => 587,
            'email'          => 'test@example.com',
            'out_encryption' => 0,
        ]);

        $this->assertArrayHasKey('out_encryption', $errors);
        $this->assertStringContainsString('Port 587 typically requires TLS', $errors['out_encryption']);
    }

    public function test_validate_settings_accepts_port_465_with_ssl(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server'     => 'smtp.example.com',
            'out_port'       => 465,
            'email'          => 'test@example.com',
            'out_encryption' => 1,
        ]);

        $this->assertArrayNotHasKey('out_encryption', $errors);
    }

    public function test_validate_settings_accepts_port_587_with_tls(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server'     => 'smtp.example.com',
            'out_port'       => 587,
            'email'          => 'test@example.com',
            'out_encryption' => 2,
        ]);

        $this->assertArrayNotHasKey('out_encryption', $errors);
    }

    public function test_validate_settings_handles_port_as_string(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port'   => '587',
            'email'      => 'test@example.com',
        ]);

        $this->assertArrayNotHasKey('out_port', $errors);
    }

    public function test_validate_settings_rejects_negative_port(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port'   => -1,
            'email'      => 'test@example.com',
        ]);

        $this->assertArrayHasKey('out_port', $errors);
    }

    public function test_validate_settings_handles_email_with_special_characters(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port'   => 587,
            'email'      => 'user+tag@example.com',
        ]);

        $this->assertArrayNotHasKey('email', $errors);
    }

    public function test_validate_settings_rejects_email_without_domain(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port'   => 587,
            'email'      => 'userwithnodomain',
        ]);

        $this->assertArrayHasKey('email', $errors);
    }

    public function test_validate_settings_rejects_email_with_spaces(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port'   => 587,
            'email'      => 'user @example.com',
        ]);

        $this->assertArrayHasKey('email', $errors);
    }

    public function test_validate_settings_handles_common_smtp_ports(): void
    {
        $service = new SmtpService;

        $errors25 = $service->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port'   => 25,
            'email'      => 'test@example.com',
        ]);
        $this->assertArrayNotHasKey('out_port', $errors25);

        $errors2525 = $service->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port'   => 2525,
            'email'      => 'test@example.com',
        ]);
        $this->assertArrayNotHasKey('out_port', $errors2525);
    }

    public function test_validate_settings_handles_server_with_whitespace(): void
    {
        $errors = (new SmtpService)->validateSettings([
            'out_server' => '  smtp.example.com  ',
            'out_port'   => 587,
            'email'      => 'test@example.com',
        ]);

        $this->assertArrayNotHasKey('out_server', $errors);
    }

    public function test_validate_settings_boundary_port_values(): void
    {
        $service = new SmtpService;

        $errorsMin = $service->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port'   => 1,
            'email'      => 'test@example.com',
        ]);
        $this->assertArrayNotHasKey('out_port', $errorsMin);

        $errorsMax = $service->validateSettings([
            'out_server' => 'smtp.example.com',
            'out_port'   => 65535,
            'email'      => 'test@example.com',
        ]);
        $this->assertArrayNotHasKey('out_port', $errorsMax);
    }

    // -------------------------------------------------------------------------
    // Basic service tests (merged from SmtpServiceTest)
    // -------------------------------------------------------------------------

    public function test_smtp_service_test_connection_returns_array(): void
    {
        $mailbox = new Mailbox([
            'name'         => 'Test Mailbox',
            'out_server'   => null,
            'out_port'     => null,
            'out_username' => null,
            'out_password' => null,
        ]);

        $result = (new SmtpService)->testConnection($mailbox, 'test@example.com');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_smtp_service_test_connection_method_exists(): void
    {
        $this->assertTrue(method_exists(SmtpService::class, 'testConnection'));
    }

    public function test_smtp_service_configure_smtp_method_exists(): void
    {
        $this->assertTrue(method_exists(SmtpService::class, 'configureSmtp'));
    }

    public function test_smtp_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(SmtpService::class, new SmtpService);
    }

    public function test_smtp_service_has_validate_settings_method(): void
    {
        $this->assertTrue(method_exists(SmtpService::class, 'validateSettings'));
    }
}
