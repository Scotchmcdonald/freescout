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
}
