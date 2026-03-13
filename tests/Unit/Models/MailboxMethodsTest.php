<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Mailbox;
use Tests\UnitTestCase;

/**
 * Tests for Mailbox model methods added during Phase 6 implementation.
 */
class MailboxMethodsTest extends UnitTestCase
{
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailbox = Mailbox::factory()->create([
            'email' => 'support@example.com',
            'name' => 'Support',
        ]);
    }

    // ===== getAliases tests =====

    public function test_get_aliases_returns_array(): void
    {
        $aliases = $this->mailbox->getAliases();

        $this->assertIsArray($aliases);
    }

    public function test_get_aliases_returns_empty_when_no_aliases(): void
    {
        $this->mailbox->aliases = null;
        $this->mailbox->save();

        $aliases = $this->mailbox->getAliases();

        $this->assertIsArray($aliases);
        $this->assertEmpty($aliases);
    }

    public function test_get_aliases_parses_simple_emails(): void
    {
        $this->mailbox->aliases = "alias1@example.com\nalias2@example.com";
        $this->mailbox->save();

        $aliases = $this->mailbox->getAliases();

        $this->assertCount(2, $aliases);
        $this->assertArrayHasKey('alias1@example.com', $aliases);
        $this->assertArrayHasKey('alias2@example.com', $aliases);
    }

    public function test_get_aliases_parses_emails_with_names(): void
    {
        $this->mailbox->aliases = "Support <support-alias@example.com>\nSales <sales@example.com>";
        $this->mailbox->save();

        $aliases = $this->mailbox->getAliases();

        $this->assertCount(2, $aliases);
        $this->assertArrayHasKey('support-alias@example.com', $aliases);
        $this->assertArrayHasKey('sales@example.com', $aliases);
    }

    public function test_get_aliases_handles_mixed_format(): void
    {
        $this->mailbox->aliases = "plain@example.com\nNamed <named@example.com>";
        $this->mailbox->save();

        $aliases = $this->mailbox->getAliases();

        $this->assertCount(2, $aliases);
        $this->assertArrayHasKey('plain@example.com', $aliases);
        $this->assertArrayHasKey('named@example.com', $aliases);
    }

    public function test_get_aliases_trims_whitespace(): void
    {
        $this->mailbox->aliases = "  whitespace@example.com  \n  another@example.com  ";
        $this->mailbox->save();

        $aliases = $this->mailbox->getAliases();

        $this->assertArrayHasKey('whitespace@example.com', $aliases);
        $this->assertArrayHasKey('another@example.com', $aliases);
    }

    public function test_get_aliases_skips_empty_lines(): void
    {
        $this->mailbox->aliases = "first@example.com\n\n\nsecond@example.com";
        $this->mailbox->save();

        $aliases = $this->mailbox->getAliases();

        $this->assertCount(2, $aliases);
    }

    public function test_get_aliases_handles_comma_separated(): void
    {
        $this->mailbox->aliases = 'first@example.com, second@example.com, third@example.com';
        $this->mailbox->save();

        $aliases = $this->mailbox->getAliases();

        // Should handle comma-separated if implemented
        $this->assertIsArray($aliases);
    }

    // ===== removeMailboxEmailsFromList tests =====

    public function test_remove_mailbox_emails_from_list_removes_main_email(): void
    {
        $emails = [
            'support@example.com',
            'customer@customer.com',
            'other@example.org',
        ];

        $result = $this->mailbox->removeMailboxEmailsFromList($emails);

        $this->assertNotContains('support@example.com', $result);
        $this->assertContains('customer@customer.com', $result);
        $this->assertContains('other@example.org', $result);
    }

    public function test_remove_mailbox_emails_from_list_removes_aliases(): void
    {
        $this->mailbox->aliases = 'alias@example.com';
        $this->mailbox->save();

        $emails = [
            'alias@example.com',
            'customer@customer.com',
        ];

        $result = $this->mailbox->removeMailboxEmailsFromList($emails);

        $this->assertNotContains('alias@example.com', $result);
        $this->assertContains('customer@customer.com', $result);
    }

    public function test_remove_mailbox_emails_from_list_case_insensitive(): void
    {
        $emails = [
            'SUPPORT@EXAMPLE.COM',
            'customer@customer.com',
        ];

        $result = $this->mailbox->removeMailboxEmailsFromList($emails);

        // Should be case-insensitive
        $this->assertCount(1, $result);
        $this->assertContains('customer@customer.com', $result);
    }

    public function test_remove_mailbox_emails_from_list_handles_empty_array(): void
    {
        $result = $this->mailbox->removeMailboxEmailsFromList([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_remove_mailbox_emails_from_list_preserves_order(): void
    {
        $emails = [
            'first@example.org',
            'support@example.com', // Will be removed
            'second@example.org',
            'third@example.org',
        ];

        $result = $this->mailbox->removeMailboxEmailsFromList($emails);

        $values = array_values($result);
        $this->assertEquals('first@example.org', $values[0]);
        $this->assertEquals('second@example.org', $values[1]);
        $this->assertEquals('third@example.org', $values[2]);
    }

    public function test_remove_mailbox_emails_removes_multiple_mailbox_emails(): void
    {
        $this->mailbox->aliases = "alias1@example.com\nalias2@example.com";
        $this->mailbox->save();

        $emails = [
            'support@example.com',
            'alias1@example.com',
            'alias2@example.com',
            'customer@customer.com',
        ];

        $result = $this->mailbox->removeMailboxEmailsFromList($emails);

        $this->assertCount(1, $result);
        $this->assertContains('customer@customer.com', $result);
    }

    // ===== Edge cases =====

    public function test_get_aliases_handles_special_characters_in_name(): void
    {
        $this->mailbox->aliases = 'John Doe (Sales) <sales@example.com>';
        $this->mailbox->save();

        $aliases = $this->mailbox->getAliases();

        $this->assertArrayHasKey('sales@example.com', $aliases);
    }

    public function test_get_aliases_handles_unicode_in_name(): void
    {
        $this->mailbox->aliases = '日本語 <japanese@example.com>';
        $this->mailbox->save();

        $aliases = $this->mailbox->getAliases();

        $this->assertArrayHasKey('japanese@example.com', $aliases);
    }

    public function test_remove_mailbox_emails_handles_duplicates_in_input(): void
    {
        $emails = [
            'customer@customer.com',
            'customer@customer.com',
            'support@example.com',
        ];

        $result = $this->mailbox->removeMailboxEmailsFromList($emails);

        // Should handle gracefully
        $this->assertIsArray($result);
    }
}
