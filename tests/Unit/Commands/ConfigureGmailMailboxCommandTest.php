<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use App\Console\Commands\ConfigureGmailMailbox;
use Tests\PureUnitTestCase;

class ConfigureGmailMailboxCommandTest extends PureUnitTestCase
{
    public function test_command_can_be_instantiated(): void
    {
        $command = new ConfigureGmailMailbox;

        $this->assertInstanceOf(ConfigureGmailMailbox::class, $command);
    }

    public function test_command_has_signature(): void
    {
        $command = new ConfigureGmailMailbox;

        $this->assertIsString($command->getName());
        $this->assertNotEmpty($command->getName());
    }

    public function test_command_has_description(): void
    {
        $command = new ConfigureGmailMailbox;

        $this->assertIsString($command->getDescription());
    }

    public function test_validation_command_signature_is_scoped_to_gmail_authorization_context(): void
    {
        // Validation boundary: the command signature must identify the Gmail
        // integration scope so that CLI authorization routing can enforce
        // that only mailbox:configure-gmail handles Gmail OAuth credentials.
        $command = new ConfigureGmailMailbox;

        $this->assertStringContainsString('gmail', $command->getName(),
            'Validation boundary: command name must identify the Gmail authorization scope'
        );
        $this->assertStringContainsString('mailbox', $command->getName(),
            'Validation boundary: command must be namespaced under mailbox for routing authorization'
        );
    }
}
