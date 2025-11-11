<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Models\Mailbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the ConfigureGmailMailbox command.
 * This command configures a mailbox with Gmail SMTP/IMAP settings.
 */
class ConfigureGmailMailboxTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test command runs successfully with valid mailbox ID.
     */
    public function test_command_runs_successfully_with_valid_inputs(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'name' => 'Test Support',
        ]);

        // Act
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
            ->expectsQuestion('Enter your Gmail address', 'test@gmail.com')
            ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'test-app-password')
            ->expectsOutput('✓ Mailbox configured successfully!')
            ->assertExitCode(0);

        // Assert
        $mailbox->refresh();
        $this->assertEquals('test@gmail.com', $mailbox->email);
        $this->assertEquals('smtp.gmail.com', $mailbox->out_server);
        $this->assertEquals(587, $mailbox->out_port);
        $this->assertEquals('test@gmail.com', $mailbox->out_username);
        $this->assertEquals(2, $mailbox->out_encryption); // TLS
        $this->assertEquals('imap.gmail.com', $mailbox->in_server);
        $this->assertEquals(993, $mailbox->in_port);
        $this->assertEquals('test@gmail.com', $mailbox->in_username);
        $this->assertEquals(1, $mailbox->in_encryption); // SSL
        $this->assertEquals(1, $mailbox->in_protocol); // IMAP
        $this->assertTrue($mailbox->in_validate_cert);

        // Verify passwords are stored
        $this->assertEquals('test-app-password', $mailbox->out_password);
        $this->assertEquals('test-app-password', $mailbox->in_password);
    }

    /**
     * Test command with default mailbox ID (1).
     */
    public function test_command_uses_default_mailbox_id_when_not_provided(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create(['id' => 1]);

        // Act - No mailbox_id argument provided, should default to 1
        $this->artisan('mailbox:configure-gmail')
            ->expectsQuestion('Enter your Gmail address', 'default@gmail.com')
            ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password123')
            ->assertExitCode(0);

        // Assert
        $mailbox->refresh();
        $this->assertEquals('default@gmail.com', $mailbox->email);
    }

    /**
     * Test command fails with invalid mailbox ID.
     */
    public function test_command_fails_with_invalid_mailbox_id(): void
    {
        // Act
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => 999])
            ->expectsOutput('Mailbox with ID 999 not found!')
            ->assertExitCode(1);
    }

    /**
     * Test command rejects invalid email address.
     */
    public function test_command_rejects_invalid_email_address(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();

        // Act
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
            ->expectsQuestion('Enter your Gmail address', 'not-an-email')
            ->expectsOutput('Invalid email address!')
            ->assertExitCode(1);

        // Assert - mailbox should not be updated
        $mailbox->refresh();
        $this->assertNotEquals('not-an-email', $mailbox->email);
    }

    /**
     * Test command rejects empty password.
     */
    public function test_command_rejects_empty_password(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $originalPassword = $mailbox->out_password;

        // Act
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
            ->expectsQuestion('Enter your Gmail address', 'valid@gmail.com')
            ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', '')
            ->expectsOutput('App Password is required!')
            ->assertExitCode(1);

        // Assert - mailbox should not be updated
        $mailbox->refresh();
        $this->assertEquals($originalPassword, $mailbox->out_password);
    }

    /**
     * Test command displays configuration summary table.
     */
    public function test_command_displays_configuration_summary(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'name' => 'Customer Support',
        ]);

        // Act & Assert
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
            ->expectsQuestion('Enter your Gmail address', 'support@gmail.com')
            ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'app-pass-16-chars')
            ->expectsOutput('Configuring mailbox: Customer Support (ID: '.$mailbox->id.')')
            ->expectsOutputToContain('smtp.gmail.com:587 (TLS)')
            ->expectsOutputToContain('imap.gmail.com:993 (SSL)')
            ->assertExitCode(0);
    }

    /**
     * Test command shows help instructions for app password.
     */
    public function test_command_shows_app_password_instructions(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();

        // Act & Assert
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
            ->expectsQuestion('Enter your Gmail address', 'test@gmail.com')
            ->expectsOutputToContain('IMPORTANT: You need a Gmail App Password')
            ->expectsOutputToContain('https://myaccount.google.com/apppasswords')
            ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
            ->assertExitCode(0);
    }

    /**
     * Test command shows next steps after configuration.
     */
    public function test_command_shows_next_steps_after_configuration(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();

        // Act & Assert
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
            ->expectsQuestion('Enter your Gmail address', 'test@gmail.com')
            ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
            ->expectsOutputToContain('Next steps:')
            ->expectsOutputToContain('php artisan freescout:fetch-emails')
            ->assertExitCode(0);
    }

    /**
     * Test command accepts various valid Gmail address formats.
     */
    public function test_command_accepts_various_email_formats(): void
    {
        // Test with standard Gmail
        $mailbox1 = Mailbox::factory()->create();
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox1->id])
            ->expectsQuestion('Enter your Gmail address', 'user@gmail.com')
            ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
            ->assertExitCode(0);

        // Test with Google Workspace email
        $mailbox2 = Mailbox::factory()->create();
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox2->id])
            ->expectsQuestion('Enter your Gmail address', 'admin@company.com')
            ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
            ->assertExitCode(0);

        // Test with dots in username
        $mailbox3 = Mailbox::factory()->create();
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox3->id])
            ->expectsQuestion('Enter your Gmail address', 'first.last@gmail.com')
            ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
            ->assertExitCode(0);

        // Assert all were configured correctly
        $mailbox1->refresh();
        $this->assertEquals('user@gmail.com', $mailbox1->email);

        $mailbox2->refresh();
        $this->assertEquals('admin@company.com', $mailbox2->email);

        $mailbox3->refresh();
        $this->assertEquals('first.last@gmail.com', $mailbox3->email);
    }

    /**
     * Test command updates both incoming and outgoing settings.
     */
    public function test_command_updates_both_incoming_and_outgoing_settings(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'old-smtp.example.com',
            'in_server' => 'old-imap.example.com',
        ]);

        // Act
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
            ->expectsQuestion('Enter your Gmail address', 'new@gmail.com')
            ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'new-password')
            ->assertExitCode(0);

        // Assert - both settings updated
        $mailbox->refresh();

        // Outgoing (SMTP)
        $this->assertEquals('smtp.gmail.com', $mailbox->out_server);
        $this->assertEquals(587, $mailbox->out_port);
        $this->assertEquals('new@gmail.com', $mailbox->out_username);
        $this->assertEquals(2, $mailbox->out_encryption); // TLS

        // Incoming (IMAP)
        $this->assertEquals('imap.gmail.com', $mailbox->in_server);
        $this->assertEquals(993, $mailbox->in_port);
        $this->assertEquals('new@gmail.com', $mailbox->in_username);
        $this->assertEquals(1, $mailbox->in_encryption); // SSL
        $this->assertEquals(1, $mailbox->in_protocol); // IMAP
        $this->assertTrue($mailbox->in_validate_cert);
    }

    /**
     * Test command handles email with special characters.
     */
    public function test_command_handles_email_with_special_characters(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();

        // Act
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
            ->expectsQuestion('Enter your Gmail address', 'user+tag@gmail.com')
            ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
            ->assertExitCode(0);

        // Assert
        $mailbox->refresh();
        $this->assertEquals('user+tag@gmail.com', $mailbox->email);
    }

    /**
     * Test command accepts whitespace in password (it's technically valid).
     */
    public function test_command_accepts_password_with_whitespace(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();

        // Act - whitespace-only password is not rejected by empty() check in PHP
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
            ->expectsQuestion('Enter your Gmail address', 'test@gmail.com')
            ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', '   ')
            ->assertExitCode(0); // Command accepts it as valid

        // Assert - password stored as entered
        $mailbox->refresh();
        $this->assertEquals('   ', $mailbox->out_password);
    }

    /**
     * Test command handles non-numeric mailbox ID gracefully.
     */
    public function test_command_handles_non_numeric_mailbox_id(): void
    {
        // Act - Laravel will handle type coercion, but non-existent ID will fail
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => 'abc'])
            ->expectsOutput('Mailbox with ID abc not found!')
            ->assertExitCode(1);
    }

    /**
     * Test command preserves mailbox name during configuration.
     */
    public function test_command_preserves_mailbox_name(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'name' => 'Important Support Mailbox',
        ]);

        // Act
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
            ->expectsQuestion('Enter your Gmail address', 'test@gmail.com')
            ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
            ->assertExitCode(0);

        // Assert - name should remain unchanged
        $mailbox->refresh();
        $this->assertEquals('Important Support Mailbox', $mailbox->name);
    }

    /**
     * Test command handles email address with uppercase letters.
     */
    public function test_command_handles_uppercase_email_address(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();

        // Act
        $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
            ->expectsQuestion('Enter your Gmail address', 'User@Gmail.COM')
            ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
            ->assertExitCode(0);

        // Assert - email stored as entered
        $mailbox->refresh();
        $this->assertEquals('User@Gmail.COM', $mailbox->email);
    }
}
