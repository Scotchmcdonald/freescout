<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Mailbox;
use Tests\PureUnitTestCase;

class MailboxAliasTest extends PureUnitTestCase
{
    private function mailbox(array|string|null $aliases, bool $aliasesReply = true): Mailbox
    {
        $mailbox = new class extends Mailbox
        {
            protected function casts(): array
            {
                return [];
            }
        };

        $mailbox->name = 'Support Team';
        $mailbox->email = 'support@example.com';
        $mailbox->aliases = $aliases;
        $mailbox->aliases_reply = $aliasesReply;

        return $mailbox;
    }

    public function test_get_aliases_returns_empty_when_reply_aliases_are_disabled(): void
    {
        $mailbox = $this->mailbox('billing@example.com', false);

        $aliases = $mailbox->getAliases(false, true);

        $this->assertSame([], $aliases);
    }

    public function test_get_aliases_parses_multiple_formats_and_ignores_invalid_entries(): void
    {
        $mailbox = $this->mailbox("Sales <sales@example.com>\ninvalid\nops@example.com(Ops Team)\n plain@example.com ");

        $aliases = $mailbox->getAliases();

        $this->assertSame([
            'sales@example.com' => 'Sales',
            'ops@example.com' => 'Ops Team',
            'plain@example.com' => '',
        ], $aliases);
    }

    public function test_get_aliases_can_include_mailbox_email_before_aliases(): void
    {
        $mailbox = $this->mailbox(['billing@example.com']);

        $aliases = $mailbox->getAliases(true, false);

        $this->assertSame('Support Team', $aliases['support@example.com']);
        $this->assertSame('', $aliases['billing@example.com']);
    }

    public function test_get_aliases_array_handles_null_array_and_csv_values(): void
    {
        $emptyMailbox = $this->mailbox(null);
        $arrayMailbox = $this->mailbox(['first@example.com', 'second@example.com']);
        $csvMailbox = $this->mailbox('first@example.com,second@example.com');

        $this->assertSame([], $emptyMailbox->getAliasesArray());
        $this->assertSame(['first@example.com', 'second@example.com'], $arrayMailbox->getAliasesArray());
        $this->assertSame(['first@example.com', 'second@example.com'], $csvMailbox->getAliasesArray());
    }

    public function test_has_alias_checks_membership_from_alias_array(): void
    {
        $mailbox = $this->mailbox(['billing@example.com', 'sales@example.com']);

        $this->assertTrue($mailbox->hasAlias('sales@example.com'));
        $this->assertFalse($mailbox->hasAlias('other@example.com'));
    }

    public function test_remove_mailbox_emails_from_list_filters_case_insensitively(): void
    {
        $mailbox = $this->mailbox(['Sales <sales@example.com>', 'billing@example.com']);

        $filtered = $mailbox->removeMailboxEmailsFromList([
            ' SUPPORT@example.com ',
            'sales@example.com',
            'Billing@example.com',
            'customer@example.com',
        ]);

        $this->assertSame([3 => 'customer@example.com'], $filtered);
    }

    public function test_fetching_and_sending_enabled_reflect_server_configuration(): void
    {
        $mailbox = $this->mailbox(null);
        $mailbox->in_server = 'imap.example.com';
        $mailbox->out_server = '';

        $this->assertTrue($mailbox->isFetchingEnabled());
        $this->assertFalse($mailbox->isSendingEnabled());

        $mailbox->in_server = null;
        $mailbox->out_server = 'smtp.example.com';

        $this->assertFalse($mailbox->isFetchingEnabled());
        $this->assertTrue($mailbox->isSendingEnabled());
    }
}
