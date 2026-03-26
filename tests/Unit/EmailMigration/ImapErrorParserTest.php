<?php

declare(strict_types=1);

namespace Tests\Unit\EmailMigration;

use Modules\EmailMigration\Services\ImapErrorParser;
use Tests\PureUnitTestCase;

final class ImapErrorParserTest extends PureUnitTestCase
{
    private ImapErrorParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ImapErrorParser;
    }

    private function ex(string $msg): \RuntimeException
    {
        return new \RuntimeException($msg);
    }

    // ─── isRateLimitError ────────────────────────────────────────────────────

    public function test_rate_limit_gmail_overquota(): void
    {
        $this->assertTrue($this->parser->isRateLimitError($this->ex('OVERQUOTA')));
    }

    public function test_rate_limit_gmail_too_many_connections(): void
    {
        $this->assertTrue($this->parser->isRateLimitError($this->ex('[ALERT] Too many simultaneous connections')));
    }

    public function test_rate_limit_gmail_too_many_logins(): void
    {
        $this->assertTrue($this->parser->isRateLimitError($this->ex('[LIMIT] Too many login attempts')));
    }

    public function test_rate_limit_gmail_try_again_minutes(): void
    {
        $this->assertTrue($this->parser->isRateLimitError($this->ex('Try again in 5 minutes')));
    }

    public function test_rate_limit_exchange_throttling_policy(): void
    {
        $this->assertTrue($this->parser->isRateLimitError($this->ex('Throttling policy exceeded')));
    }

    public function test_rate_limit_exchange_transient(): void
    {
        $this->assertTrue($this->parser->isRateLimitError($this->ex('ErrorConnectionFailedTransientException')));
    }

    public function test_rate_limit_yahoo_unavailable(): void
    {
        $this->assertTrue($this->parser->isRateLimitError($this->ex('[UNAVAILABLE] System error')));
    }

    public function test_rate_limit_generic_rate_limit_exceeded(): void
    {
        $this->assertTrue($this->parser->isRateLimitError($this->ex('Rate limit exceeded')));
    }

    public function test_rate_limit_generic_too_many_requests(): void
    {
        $this->assertTrue($this->parser->isRateLimitError($this->ex('Too many requests')));
    }

    public function test_rate_limit_generic_connection_refused(): void
    {
        // Connection refused is treated as rate limit (backlog full)
        $this->assertTrue($this->parser->isRateLimitError($this->ex('Connection refused')));
    }

    public function test_rate_limit_case_insensitive(): void
    {
        $this->assertTrue($this->parser->isRateLimitError($this->ex('rate limit exceeded')));
    }

    public function test_not_rate_limit_for_unrelated_message(): void
    {
        $this->assertFalse($this->parser->isRateLimitError($this->ex('Something went wrong')));
    }

    // ─── isAuthError ─────────────────────────────────────────────────────────

    public function test_auth_error_authentication_failed(): void
    {
        $this->assertTrue($this->parser->isAuthError($this->ex('[AUTHENTICATIONFAILED] Bad credentials')));
    }

    public function test_auth_error_invalid_credentials(): void
    {
        $this->assertTrue($this->parser->isAuthError($this->ex('Invalid credentials')));
    }

    public function test_auth_error_login_failed(): void
    {
        $this->assertTrue($this->parser->isAuthError($this->ex('Login failed')));
    }

    public function test_auth_error_bad_username_or_password(): void
    {
        $this->assertTrue($this->parser->isAuthError($this->ex('Bad username or password')));
    }

    public function test_auth_error_case_insensitive(): void
    {
        $this->assertTrue($this->parser->isAuthError($this->ex('invalid credentials')));
    }

    public function test_not_auth_error_for_unrelated_message(): void
    {
        $this->assertFalse($this->parser->isAuthError($this->ex('Connection timed out')));
    }

    // ─── isNetworkError ───────────────────────────────────────────────────────

    public function test_network_error_connection_timed_out(): void
    {
        $this->assertTrue($this->parser->isNetworkError($this->ex('Connection timed out')));
    }

    public function test_network_error_host_not_found(): void
    {
        $this->assertTrue($this->parser->isNetworkError($this->ex('Host not found')));
    }

    public function test_network_error_ssl(): void
    {
        $this->assertTrue($this->parser->isNetworkError($this->ex('SSL handshake error')));
    }

    public function test_network_error_tls(): void
    {
        $this->assertTrue($this->parser->isNetworkError($this->ex('TLS negotiation error')));
    }

    public function test_not_network_error_for_auth_message(): void
    {
        $this->assertFalse($this->parser->isNetworkError($this->ex('Login failed')));
    }

    // ─── isQuotaError ─────────────────────────────────────────────────────────

    public function test_quota_error_quota_exceeded(): void
    {
        $this->assertTrue($this->parser->isQuotaError($this->ex('Quota exceeded')));
    }

    public function test_quota_error_mailbox_full(): void
    {
        $this->assertTrue($this->parser->isQuotaError($this->ex('Mailbox full')));
    }

    public function test_quota_error_storage_quota(): void
    {
        $this->assertTrue($this->parser->isQuotaError($this->ex('User has exceeded their storage quota')));
    }

    public function test_quota_error_disk_quota_exceeded(): void
    {
        $this->assertTrue($this->parser->isQuotaError($this->ex('Disk quota exceeded')));
    }

    public function test_not_quota_error_for_unrelated_message(): void
    {
        $this->assertFalse($this->parser->isQuotaError($this->ex('Rate limit exceeded')));
    }

    // ─── isAppPasswordError ───────────────────────────────────────────────────

    public function test_app_password_error_application_specific(): void
    {
        $this->assertTrue($this->parser->isAppPasswordError($this->ex('Application-specific password required')));
    }

    public function test_app_password_error_web_browser(): void
    {
        $this->assertTrue($this->parser->isAppPasswordError($this->ex('Please log in via your web browser')));
    }

    public function test_app_password_error_web_login_required(): void
    {
        $this->assertTrue($this->parser->isAppPasswordError($this->ex('Web login required')));
    }

    public function test_not_app_password_error_for_unrelated_message(): void
    {
        $this->assertFalse($this->parser->isAppPasswordError($this->ex('Invalid credentials')));
    }

    // ─── categorize ───────────────────────────────────────────────────────────

    public function test_categorize_rate_limit_returns_correct_category(): void
    {
        $result = $this->parser->categorize($this->ex('Rate limit exceeded'));

        $this->assertSame('rate_limit', $result['category']);
        $this->assertSame('warning', $result['severity']);
        $this->assertTrue($result['is_recoverable']);
        $this->assertArrayHasKey('retry_after', $result);
    }

    public function test_categorize_auth_error_returns_authentication_category(): void
    {
        $result = $this->parser->categorize($this->ex('Invalid credentials'));

        $this->assertSame('authentication', $result['category']);
        $this->assertSame('error', $result['severity']);
        $this->assertFalse($result['is_recoverable']);
    }

    public function test_categorize_app_password_takes_priority_over_generic_auth(): void
    {
        $result = $this->parser->categorize($this->ex('Web login required'));

        $this->assertSame('authentication', $result['category']);
        $this->assertSame('App Password Req.', $result['badge_message']);
        $this->assertFalse($result['is_recoverable']);
    }

    public function test_categorize_quota_error_returns_quota_category(): void
    {
        $result = $this->parser->categorize($this->ex('Mailbox full'));

        $this->assertSame('quota', $result['category']);
        $this->assertFalse($result['is_recoverable']);
    }

    public function test_categorize_network_error_returns_network_category(): void
    {
        $result = $this->parser->categorize($this->ex('Connection timed out'));

        $this->assertSame('network', $result['category']);
        $this->assertTrue($result['is_recoverable']);
    }

    public function test_categorize_unknown_error_returns_original_message(): void
    {
        $msg = 'Some completely unknown error occurred';
        $result = $this->parser->categorize($this->ex($msg));

        $this->assertSame('unknown', $result['category']);
        $this->assertSame($msg, $result['user_message']);
        $this->assertFalse($result['is_recoverable']);
    }

    public function test_categorize_result_always_has_required_keys(): void
    {
        $result = $this->parser->categorize($this->ex('Some random error'));

        $this->assertArrayHasKey('category', $result);
        $this->assertArrayHasKey('severity', $result);
        $this->assertArrayHasKey('user_message', $result);
        $this->assertArrayHasKey('badge_icon', $result);
        $this->assertArrayHasKey('badge_message', $result);
        $this->assertArrayHasKey('retry_after', $result);
        $this->assertArrayHasKey('is_recoverable', $result);
    }
}
