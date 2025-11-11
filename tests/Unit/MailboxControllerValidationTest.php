<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Mailbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MailboxControllerValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that mailbox name is required for creation.
     */
    public function test_mailbox_name_is_required_for_creation(): void
    {
        // Arrange
        $data = [
            'email' => 'test@example.com',
            'in_server' => 'imap.example.com',
            'in_port' => 993,
        ];

        // Act
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:mailboxes,email',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /**
     * Test that mailbox email must be unique.
     */
    public function test_mailbox_email_must_be_unique(): void
    {
        // Arrange
        Mailbox::factory()->create(['email' => 'existing@example.com']);

        $data = [
            'name' => 'New Mailbox',
            'email' => 'existing@example.com',
        ];

        // Act
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:mailboxes,email',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /**
     * Test that mailbox email can remain same on update (except for itself).
     */
    public function test_mailbox_email_can_remain_same_on_update(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create(['email' => 'existing@example.com']);

        $data = [
            'name' => 'Updated Mailbox',
            'email' => 'existing@example.com',
        ];

        // Act - Use the ignore rule for updates
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:mailboxes,email,'.$mailbox->id,
        ]);

        // Assert
        $this->assertFalse($validator->fails());
    }

    /**
     * Test that in_port must be an integer.
     */
    public function test_in_port_must_be_integer(): void
    {
        // Arrange
        $data = [
            'name' => 'Test Mailbox',
            'email' => 'test@example.com',
            'in_port' => 'not-a-number',
        ];

        // Act
        $validator = Validator::make($data, [
            'in_port' => 'nullable|integer',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('in_port', $validator->errors()->toArray());
    }

    /**
     * Test that out_port must be an integer.
     */
    public function test_out_port_must_be_integer(): void
    {
        // Arrange
        $data = [
            'name' => 'Test Mailbox',
            'email' => 'test@example.com',
            'out_port' => 'invalid-port',
        ];

        // Act
        $validator = Validator::make($data, [
            'out_port' => 'nullable|integer',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('out_port', $validator->errors()->toArray());
    }

    /**
     * Test that in_protocol must be valid value.
     */
    public function test_in_protocol_must_be_valid(): void
    {
        // Arrange
        $data = [
            'in_protocol' => 'invalid-protocol',
        ];

        // Act
        $validator = Validator::make($data, [
            'in_protocol' => 'nullable|in:imap,pop3',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('in_protocol', $validator->errors()->toArray());
    }

    /**
     * Test that out_method must be valid value.
     */
    public function test_out_method_must_be_valid(): void
    {
        // Arrange
        $data = [
            'out_method' => 'carrier-pigeon',
        ];

        // Act
        $validator = Validator::make($data, [
            'out_method' => 'nullable|in:mail,smtp',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('out_method', $validator->errors()->toArray());
    }

    /**
     * Test that in_encryption must be valid value.
     */
    public function test_in_encryption_must_be_valid(): void
    {
        // Arrange
        $data = [
            'in_encryption' => 'super-encryption',
        ];

        // Act
        $validator = Validator::make($data, [
            'in_encryption' => 'nullable|in:none,ssl,tls',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('in_encryption', $validator->errors()->toArray());
    }

    /**
     * Test that out_encryption must be valid value.
     */
    public function test_out_encryption_must_be_valid(): void
    {
        // Arrange
        $data = [
            'out_encryption' => 'quantum-encryption',
        ];

        // Act
        $validator = Validator::make($data, [
            'out_encryption' => 'nullable|in:none,ssl,tls',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('out_encryption', $validator->errors()->toArray());
    }

    /**
     * Test that auto_reply_enabled must be boolean.
     */
    public function test_auto_reply_enabled_must_be_boolean(): void
    {
        // Arrange
        $data = [
            'auto_reply_enabled' => 'yes-please',
        ];

        // Act
        $validator = Validator::make($data, [
            'auto_reply_enabled' => 'nullable|boolean',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('auto_reply_enabled', $validator->errors()->toArray());
    }

    /**
     * Test that valid mailbox data passes validation.
     */
    public function test_valid_mailbox_data_passes_validation(): void
    {
        // Arrange
        $data = [
            'name' => 'Support Mailbox',
            'email' => 'support@example.com',
            'from_name' => 'Support Team',
            'out_method' => 'smtp',
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'user@example.com',
            'out_password' => 'secret123',
            'out_encryption' => 'tls',
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'user@example.com',
            'in_password' => 'secret123',
            'in_protocol' => 'imap',
            'in_encryption' => 'ssl',
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Thank you',
            'auto_reply_message' => 'We received your message',
        ];

        // Act
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:mailboxes,email',
            'from_name' => 'nullable|string|max:255',
            'out_method' => 'nullable|in:mail,smtp',
            'out_server' => 'nullable|string|max:255',
            'out_port' => 'nullable|integer',
            'out_username' => 'nullable|string|max:255',
            'out_password' => 'nullable|string',
            'out_encryption' => 'nullable|in:none,ssl,tls',
            'in_server' => 'nullable|string|max:255',
            'in_port' => 'nullable|integer',
            'in_username' => 'nullable|string|max:255',
            'in_password' => 'nullable|string',
            'in_protocol' => 'nullable|in:imap,pop3',
            'in_encryption' => 'nullable|in:none,ssl,tls',
            'auto_reply_enabled' => 'nullable|boolean',
            'auto_reply_subject' => 'nullable|string|max:255',
            'auto_reply_message' => 'nullable|string',
        ]);

        // Assert
        $this->assertFalse($validator->fails());
    }
}
