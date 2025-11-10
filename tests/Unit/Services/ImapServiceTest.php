<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImapService;
use Tests\TestCase;

class ImapServiceTest extends TestCase
{
    public function test_service_can_be_instantiated(): void
    {
        $service = new ImapService();

        $this->assertInstanceOf(ImapService::class, $service);
    }

    public function test_service_has_fetch_emails_method(): void
    {
        $service = new ImapService();

        $this->assertTrue(method_exists($service, 'fetchEmails'));
    }

    public function test_service_has_test_connection_method(): void
    {
        $service = new ImapService();

        $this->assertTrue(method_exists($service, 'testConnection'));
    }
}
