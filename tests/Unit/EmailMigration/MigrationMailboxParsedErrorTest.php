<?php

declare(strict_types=1);

namespace Tests\Unit\EmailMigration;

use Modules\EmailMigration\Models\MigrationMailbox;
use Tests\PureUnitTestCase;

final class StubMigrationMailbox extends MigrationMailbox
{
    protected static function booted(): void {}

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}

final class MigrationMailboxParsedErrorTest extends PureUnitTestCase
{
    private function make(?string $lastError): StubMigrationMailbox
    {
        $mailbox = new StubMigrationMailbox;
        $mailbox->setRawAttributes(['last_error' => $lastError]);

        return $mailbox;
    }

    public function test_parsed_error_empty_last_error_returns_unknown(): void
    {
        $result = $this->make(null)->parsed_error;

        $this->assertSame('unknown', $result['type']);
        $this->assertSame('Unknown', $result['label']);
        $this->assertSame('gray', $result['color']);
    }

    public function test_parsed_error_empty_string_returns_unknown(): void
    {
        $result = $this->make('')->parsed_error;

        $this->assertSame('unknown', $result['type']);
    }

    public function test_parsed_error_rate_limit_returns_yellow_and_retrying(): void
    {
        $result = $this->make('Rate limit exceeded')->parsed_error;

        $this->assertSame('rate_limit', $result['type']);
        $this->assertSame('yellow', $result['color']);
        $this->assertSame('Retrying...', $result['advice']);
    }

    public function test_parsed_error_auth_failure_returns_red_and_action_required(): void
    {
        $result = $this->make('Invalid credentials')->parsed_error;

        $this->assertSame('authentication', $result['type']);
        $this->assertSame('red', $result['color']);
        $this->assertSame('Action Req.', $result['advice']);
    }

    public function test_parsed_error_quota_error_returns_red(): void
    {
        $result = $this->make('Mailbox full')->parsed_error;

        $this->assertSame('quota', $result['type']);
        $this->assertSame('red', $result['color']);
        $this->assertSame('Action Req.', $result['advice']);
    }

    public function test_parsed_error_network_error_returns_orange(): void
    {
        $result = $this->make('Connection timed out')->parsed_error;

        $this->assertSame('network', $result['type']);
        $this->assertSame('orange', $result['color']);
        $this->assertSame('Retrying...', $result['advice']);
    }

    public function test_parsed_error_unknown_message_returns_red(): void
    {
        $result = $this->make('Completely unknown IMAP error')->parsed_error;

        $this->assertSame('unknown', $result['type']);
        $this->assertSame('red', $result['color']);
        $this->assertSame('Action Req.', $result['advice']);
    }

    public function test_parsed_error_has_required_keys(): void
    {
        $result = $this->make('Some error')->parsed_error;

        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayHasKey('color', $result);
        $this->assertArrayHasKey('advice', $result);
    }
}
