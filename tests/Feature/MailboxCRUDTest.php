<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailboxCRUDTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->user = User::factory()->create(['role' => User::ROLE_USER]);
    }

    /**
     * Test admin can create a new mailbox.
     */
    public function test_admin_can_create_mailbox(): void
    {
        // Arrange
        $mailboxData = [
            'name' => 'New Support Mailbox',
            'email' => 'newsupport@example.com',
            'from_name' => 'Support Team',
        ];

        // Act
        $response = $this->actingAs($this->admin)->post(route('mailboxes.store'), $mailboxData);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('mailboxes', [
            'name' => 'New Support Mailbox',
            'email' => 'newsupport@example.com',
            'from_name' => 3, // custom
            'from_name_custom' => 'Support Team',
        ]);
    }

    /**
     * Test non-admin cannot create mailbox.
     */
    public function test_non_admin_cannot_create_mailbox(): void
    {
        // Arrange
        $mailboxData = [
            'name' => 'Unauthorized Mailbox',
            'email' => 'unauthorized@example.com',
        ];

        // Act
        $response = $this->actingAs($this->user)->post(route('mailboxes.store'), $mailboxData);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseMissing('mailboxes', [
            'email' => 'unauthorized@example.com',
        ]);
    }

    /**
     * Test mailbox creation validates email format.
     */
    public function test_mailbox_creation_validates_email_format(): void
    {
        // Arrange
        $mailboxData = [
            'name' => 'Test Mailbox',
            'email' => 'invalid-email',
        ];

        // Act
        $response = $this->actingAs($this->admin)->post(route('mailboxes.store'), $mailboxData);

        // Assert
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('mailboxes', [
            'name' => 'Test Mailbox',
        ]);
    }

    /**
     * Test mailbox creation requires unique email.
     */
    public function test_mailbox_creation_requires_unique_email(): void
    {
        // Arrange
        $existing = Mailbox::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $mailboxData = [
            'name' => 'Duplicate Email Mailbox',
            'email' => 'existing@example.com',
        ];

        // Act
        $response = $this->actingAs($this->admin)->post(route('mailboxes.store'), $mailboxData);

        // Assert
        $response->assertSessionHasErrors('email');
        $this->assertEquals(1, Mailbox::where('email', 'existing@example.com')->count());
    }

    /**
     * Test admin can update mailbox.
     */
    public function test_admin_can_update_mailbox(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'from_name' => 'Updated Team',
        ];

        // Act
        $response = $this->actingAs($this->admin)->patch(route('mailboxes.update', $mailbox), $updateData);

        // Assert
        $response->assertRedirect();
        $mailbox->refresh();
        $this->assertEquals('Updated Name', $mailbox->name);
        $this->assertEquals(3, $mailbox->from_name);
        $this->assertEquals('Updated Team', $mailbox->from_name_custom);
    }

    /**
     * Test non-admin cannot update mailbox.
     */
    public function test_non_admin_cannot_update_mailbox(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'name' => 'Original Name',
        ]);

        Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $updateData = [
            'name' => 'Hacked Name',
        ];

        // Act
        $response = $this->actingAs($this->user)->patch(route('mailboxes.update', $mailbox), $updateData);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas('mailboxes', [
            'id' => $mailbox->id,
            'name' => 'Original Name',
        ]);
    }

    /**
     * Test mailbox update validates data.
     */
    public function test_mailbox_update_validates_data(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();

        Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $updateData = [
            'name' => '', // Empty name
        ];

        // Act
        $response = $this->actingAs($this->admin)->patch(route('mailboxes.update', $mailbox), $updateData);

        // Assert
        $response->assertSessionHasErrors('name');
    }

    /**
     * Test admin can delete mailbox.
     */
    public function test_admin_can_delete_mailbox(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();

        Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        // Act
        $response = $this->actingAs($this->admin)->delete(route('mailboxes.destroy', $mailbox));

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseMissing('mailboxes', [
            'id' => $mailbox->id,
        ]);
    }

    /**
     * Test non-admin cannot delete mailbox.
     */
    public function test_non_admin_cannot_delete_mailbox(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();

        Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        // Act
        $response = $this->actingAs($this->user)->delete(route('mailboxes.destroy', $mailbox));

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas('mailboxes', [
            'id' => $mailbox->id,
        ]);
    }

    /**
     * Test guest cannot access mailbox operations.
     */
    public function test_guest_cannot_access_mailbox_crud_operations(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();

        // Test create
        $response1 = $this->post(route('mailboxes.store'), ['name' => 'Test']);
        $response1->assertRedirect(route('login'));

        // Test update
        $response2 = $this->patch(route('mailboxes.update', $mailbox), ['name' => 'Test']);
        $response2->assertRedirect(route('login'));

        // Test delete
        $response3 = $this->delete(route('mailboxes.destroy', $mailbox));
        $response3->assertRedirect(route('login'));
    }

    /**
     * Test mailbox creation with all optional fields.
     */
    public function test_mailbox_creation_with_optional_fields(): void
    {
        // Arrange
        $mailboxData = [
            'name' => 'Full Feature Mailbox',
            'email' => 'full@example.com',
            'from_name' => 'Full Team',
            'from_name_custom' => 'Custom Name',
            'ticket_status' => 1,
            'template' => 'default',
            'signature' => 'Best regards,\nSupport Team',
        ];

        // Act
        $response = $this->actingAs($this->admin)->post(route('mailboxes.store'), $mailboxData);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('mailboxes', [
            'email' => 'full@example.com',
            'from_name' => 3,
            'from_name_custom' => 'Full Team',
        ]);
    }
}
