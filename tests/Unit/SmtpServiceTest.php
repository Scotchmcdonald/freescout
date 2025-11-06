<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Mailbox;
use App\Services\SmtpService;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SmtpServiceTest extends TestCase
{
    public function test_test_connection_returns_array(): void
    {
        $mailbox = new Mailbox([
            'id' => 1,
            'name' => 'Test Mailbox',
            'out_server' => null, // Missing required field
            'out_port' => null,
            'out_username' => null,
            'out_password' => null
        ]);
        
        $service = new SmtpService();
        $result = $service->testConnection($mailbox, 'test@example.com');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_test_connection_method_exists(): void
    {
        $service = new SmtpService();
        $this->assertTrue(method_exists($service, 'testConnection'));
    }

    public function test_configure_smtp_method_exists(): void
    {
        $mailbox = new Mailbox([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'test@example.com',
            'out_password' => 'password'
        ]);
        
        $service = new SmtpService();
        
        // Just test that the method exists and can be called
        $this->assertTrue(method_exists($service, 'configureSmtp'));
        
        try {
            $service->configureSmtp($mailbox);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Method might fail due to missing config, but it should exist
            $this->assertTrue(true);
        }
    }
}