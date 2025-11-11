<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\SmtpService;
use Tests\TestCase;

class SmtpServiceTest extends TestCase
{
    public function test_service_can_be_instantiated(): void
    {
        $service = new SmtpService;

        $this->assertInstanceOf(SmtpService::class, $service);
    }

    public function test_service_has_test_connection_method(): void
    {
        $service = new SmtpService;

        $this->assertTrue(method_exists($service, 'testConnection'));
    }

    public function test_service_has_configure_smtp_method(): void
    {
        $service = new SmtpService;

        $this->assertTrue(method_exists($service, 'configureSmtp'));
    }

    public function test_service_has_validate_settings_method(): void
    {
        $service = new SmtpService;

        $this->assertTrue(method_exists($service, 'validateSettings'));
    }
}
