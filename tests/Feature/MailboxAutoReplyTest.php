<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Additional comprehensive tests for Mailbox Auto-Reply functionality.
 * These tests complement the existing MailboxTest.php auto-reply test.
 */
class MailboxAutoReplyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->mailbox = Mailbox::factory()->create();
    }

    /**
     * Test admin can view auto-reply settings page.
     */
    public function test_admin_can_view_auto_reply_settings_page(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Act
        $response = $this->get(route('mailboxes.auto_reply', $this->mailbox));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Auto Reply');
    }

    /**
     * Test non-admin cannot view auto-reply settings page.
     */
    public function test_non_admin_cannot_view_auto_reply_settings_page(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('mailboxes.auto_reply', $this->mailbox));

        // Assert
        $response->assertStatus(403);
    }

    /**
     * Test admin can enable auto-reply with all required fields.
     */
    public function test_admin_can_enable_auto_reply_with_required_fields(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Act
        $response = $this->post(route('mailboxes.auto_reply.save', $this->mailbox), [
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Thank you for your message',
            'auto_reply_message' => 'We have received your message and will respond within 24 hours.',
        ]);

        // Assert
        $response->assertRedirect(route('mailboxes.auto_reply', $this->mailbox));
        $response->assertSessionHas('success');

        $this->mailbox->refresh();
        $this->assertTrue($this->mailbox->auto_reply_enabled);
        $this->assertEquals('Thank you for your message', $this->mailbox->auto_reply_subject);
        $this->assertEquals('We have received your message and will respond within 24 hours.', $this->mailbox->auto_reply_message);
    }

    /**
     * Test admin can disable auto-reply.
     */
    public function test_admin_can_disable_auto_reply(): void
    {
        // Arrange
        $this->actingAs($this->admin);
        $this->mailbox->update([
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Old Subject',
            'auto_reply_message' => 'Old Message',
        ]);

        // Act - Don't include auto_reply_enabled in request to disable it
        $response = $this->post(route('mailboxes.auto_reply.save', $this->mailbox), [
            'auto_reply_subject' => '',
            'auto_reply_message' => '',
        ]);

        // Assert
        $response->assertRedirect(route('mailboxes.auto_reply', $this->mailbox));

        $this->mailbox->refresh();
        // Since auto_reply_enabled is not in the request, has() returns false
        $this->assertFalse($this->mailbox->auto_reply_enabled);
    }

    /**
     * Test auto-reply requires subject when enabled.
     */
    public function test_auto_reply_requires_subject_when_enabled(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Act
        $response = $this->post(route('mailboxes.auto_reply.save', $this->mailbox), [
            'auto_reply_enabled' => true,
            'auto_reply_subject' => '', // Missing subject
            'auto_reply_message' => 'Some message',
        ]);

        // Assert
        $response->assertSessionHasErrors('auto_reply_subject');
    }

    /**
     * Test auto-reply requires message when enabled.
     */
    public function test_auto_reply_requires_message_when_enabled(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Act
        $response = $this->post(route('mailboxes.auto_reply.save', $this->mailbox), [
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Thank you',
            'auto_reply_message' => '', // Missing message
        ]);

        // Assert
        $response->assertSessionHasErrors('auto_reply_message');
    }

    /**
     * Test auto-reply subject has max length validation.
     */
    public function test_auto_reply_subject_has_max_length(): void
    {
        // Arrange
        $this->actingAs($this->admin);
        $longSubject = str_repeat('A', 129); // Exceeds 128 char limit

        // Act
        $response = $this->post(route('mailboxes.auto_reply.save', $this->mailbox), [
            'auto_reply_enabled' => true,
            'auto_reply_subject' => $longSubject,
            'auto_reply_message' => 'Message',
        ]);

        // Assert
        $response->assertSessionHasErrors('auto_reply_subject');
    }

    /**
     * Test auto-reply can be saved with auto_bcc email.
     */
    public function test_auto_reply_can_include_auto_bcc_email(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Act
        $response = $this->post(route('mailboxes.auto_reply.save', $this->mailbox), [
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Thank you',
            'auto_reply_message' => 'We will respond soon',
            'auto_bcc' => 'archive@example.com',
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->mailbox->refresh();
        $this->assertEquals('archive@example.com', $this->mailbox->auto_bcc);
    }

    /**
     * Test auto_bcc must be valid email format.
     */
    public function test_auto_bcc_must_be_valid_email(): void
    {
        // Arrange
        $this->actingAs($this->admin);

        // Act
        $response = $this->post(route('mailboxes.auto_reply.save', $this->mailbox), [
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Thank you',
            'auto_reply_message' => 'Message',
            'auto_bcc' => 'not-an-email',
        ]);

        // Assert
        $response->assertSessionHasErrors('auto_bcc');
    }

    /**
     * Test non-admin cannot save auto-reply settings.
     */
    public function test_non_admin_cannot_save_auto_reply_settings(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        // Act
        $response = $this->post(route('mailboxes.auto_reply.save', $this->mailbox), [
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Subject',
            'auto_reply_message' => 'Message',
        ]);

        // Assert
        $response->assertStatus(403);
    }
}
