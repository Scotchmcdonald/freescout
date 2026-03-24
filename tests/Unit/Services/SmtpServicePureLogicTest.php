<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Mailbox;
use App\Services\SmtpService;
use Tests\PureUnitTestCase;

class SmtpServicePureLogicTest extends PureUnitTestCase
{
    private SmtpService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new class extends SmtpService
        {
            public function callValidateMailboxSettings(Mailbox $mailbox): array
            {
                return $this->validateMailboxSettings($mailbox);
            }

            public function callGetEncryption(int|string|null $encryption): ?string
            {
                return $this->getEncryption($encryption);
            }

            public function callDecryptPassword(?string $password): string
            {
                return $this->decryptPassword($password);
            }
        };
    }

    public function test_validate_mailbox_settings_reports_missing_required_fields(): void
    {
        $mailbox = new Mailbox([
            'out_server' => '',
            'out_port' => null,
            'email' => '',
        ]);

        $errors = $this->service->callValidateMailboxSettings($mailbox);

        $this->assertContains('SMTP server not configured', $errors);
        $this->assertContains('SMTP port not configured', $errors);
        $this->assertContains('From email address not configured', $errors);
    }

    public function test_validate_mailbox_settings_rejects_invalid_email_format(): void
    {
        $mailbox = new Mailbox([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'invalid-email',
        ]);

        $errors = $this->service->callValidateMailboxSettings($mailbox);

        $this->assertSame(['Invalid from email address'], $errors);
    }

    public function test_validate_mailbox_settings_accepts_valid_configuration(): void
    {
        $mailbox = new Mailbox([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'email' => 'noreply@example.com',
        ]);

        $errors = $this->service->callValidateMailboxSettings($mailbox);

        $this->assertSame([], $errors);
    }

    public function test_get_encryption_maps_integer_and_string_values(): void
    {
        $this->assertSame('ssl', $this->service->callGetEncryption(1));
        $this->assertSame('tls', $this->service->callGetEncryption(2));
        $this->assertSame('ssl', $this->service->callGetEncryption('1'));
        $this->assertNull($this->service->callGetEncryption(0));
        $this->assertNull($this->service->callGetEncryption(null));
    }

    public function test_decrypt_password_returns_empty_string_for_empty_input(): void
    {
        $this->assertSame('', $this->service->callDecryptPassword(null));
        $this->assertSame('', $this->service->callDecryptPassword(''));
    }

    public function test_decrypt_password_falls_back_to_original_value_when_decryption_fails(): void
    {
        $raw = 'not-encrypted';

        $this->assertSame($raw, $this->service->callDecryptPassword($raw));
    }
}
