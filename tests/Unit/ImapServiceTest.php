<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Mailbox;
use App\Services\ImapService;
use Tests\TestCase;

class ImapServiceTest extends TestCase
{
    public function test_fetch_emails_returns_array_with_no_server(): void
    {
        $mailbox = new Mailbox([
            'id' => 1,
            'name' => 'Test Mailbox',
            'in_server' => null,
        ]);

        $service = new ImapService;
        $result = $service->fetchEmails($mailbox);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('fetched', $result);
        $this->assertArrayHasKey('created', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('messages', $result);
    }

    public function test_fetch_emails_method_exists(): void
    {
        $service = new ImapService;
        $this->assertTrue(method_exists($service, 'fetchEmails'));
    }
}
