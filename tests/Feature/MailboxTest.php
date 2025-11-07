<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailboxTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_admin_can_view_mailboxes_list(): void
    {
        $this->actingAs($this->admin);

        $mailbox1 = Mailbox::factory()->create([
            'name' => 'Support',
            'email' => 'support@example.com',
        ]);
        $mailbox2 = Mailbox::factory()->create([
            'name' => 'Sales',
            'email' => 'sales@example.com',
        ]);

        $response = $this->get(route('mailboxes.index'));

        $response->assertOk();
        $response->assertViewIs('mailboxes.index');
        $response->assertSee('Support');
        $response->assertSee('support@example.com');
        $response->assertSee('Sales');
        $response->assertSee('sales@example.com');
    }

    public function test_admin_can_create_mailbox(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('mailboxes.store'), [
            'name' => 'New Mailbox',
            'email' => 'new@example.com',
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'user@example.com',
            'in_password' => 'password',
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'user@example.com',
            'out_password' => 'password',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('mailboxes', [
            'name' => 'New Mailbox',
            'email' => 'new@example.com',
        ]);
    }

    public function test_admin_can_update_mailbox(): void
    {
        $this->actingAs($this->admin);

        $mailbox = Mailbox::factory()->create();

        $response = $this->put(route('mailboxes.update', $mailbox), [
            'name' => 'Updated Mailbox',
            'email' => $mailbox->email,
            'in_server' => $mailbox->in_server,
            'in_port' => $mailbox->in_port,
            'in_username' => $mailbox->in_username,
            'out_server' => $mailbox->out_server,
            'out_port' => $mailbox->out_port,
            'out_username' => $mailbox->out_username,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('mailboxes', [
            'id' => $mailbox->id,
            'name' => 'Updated Mailbox',
        ]);
    }

    public function test_admin_can_delete_mailbox(): void
    {
        $this->actingAs($this->admin);

        $mailbox = Mailbox::factory()->create();

        $response = $this->delete(route('mailboxes.destroy', $mailbox));

        $response->assertRedirect();

        $this->assertDatabaseMissing('mailboxes', [
            'id' => $mailbox->id,
        ]);
    }

    public function test_non_admin_cannot_create_mailbox(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user);

        $response = $this->post(route('mailboxes.store'), [
            'name' => 'New Mailbox',
            'email' => 'new@example.com',
        ]);

        $response->assertForbidden();
    }

    public function test_mailbox_requires_unique_email(): void
    {
        $this->actingAs($this->admin);

        $existingMailbox = Mailbox::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->post(route('mailboxes.store'), [
            'name' => 'Duplicate Mailbox',
            'email' => 'existing@example.com',
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_mailbox_can_have_users_attached(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();

        $mailbox->users()->attach($user);

        $this->assertTrue($mailbox->users->contains($user));
    }

    public function test_mailbox_auto_reply_settings(): void
    {
        $this->actingAs($this->admin);

        $mailbox = Mailbox::factory()->create();

        $response = $this->put(route('mailboxes.update', $mailbox), [
            'name' => $mailbox->name,
            'email' => $mailbox->email,
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'We received your message',
            'auto_reply_message' => 'Thank you for contacting us!',
            'in_server' => $mailbox->in_server,
            'in_port' => $mailbox->in_port,
            'in_username' => $mailbox->in_username,
            'out_server' => $mailbox->out_server,
            'out_port' => $mailbox->out_port,
            'out_username' => $mailbox->out_username,
        ]);

        $mailbox->refresh();

        $this->assertTrue($mailbox->auto_reply_enabled);
        $this->assertEquals('We received your message', $mailbox->auto_reply_subject);
    }
}
