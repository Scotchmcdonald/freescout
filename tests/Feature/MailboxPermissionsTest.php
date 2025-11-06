<?php

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\User;
use App\Policies\MailboxPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MailboxPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $user1;
    protected User $user2;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create();
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();
        $this->mailbox = Mailbox::factory()->create();
    }

    #[Test]
    public function admin_can_view_permissions_page()
    {
        $this->actingAs($this->adminUser);

        $this->get(route('mailboxes.permissions', $this->mailbox))
            ->assertStatus(200)
            ->assertSee('Mailbox Permissions')
            ->assertSee($this->user1->getFullName())
            ->assertSee($this->user2->getFullName());
    }

    #[Test]
    public function non_admin_cannot_view_permissions_page()
    {
        $this->actingAs($this->user1);

        $this->get(route('mailboxes.permissions', $this->mailbox))
            ->assertStatus(403);
    }

    #[Test]
    public function admin_can_update_permissions()
    {
        $this->actingAs($this->adminUser);

        $permissions = [
            $this->user1->id => MailboxPolicy::ACCESS_REPLY,
            $this->user2->id => MailboxPolicy::ACCESS_VIEW,
        ];

        $this->post(route('mailboxes.permissions.update', $this->mailbox), ['permissions' => $permissions])
            ->assertRedirect(route('mailboxes.permissions', $this->mailbox))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('mailbox_user', [
            'mailbox_id' => $this->mailbox->id,
            'user_id' => $this->user1->id,
            'access' => MailboxPolicy::ACCESS_REPLY,
        ]);

        $this->assertDatabaseHas('mailbox_user', [
            'mailbox_id' => $this->mailbox->id,
            'user_id' => $this->user2->id,
            'access' => MailboxPolicy::ACCESS_VIEW,
        ]);
    }

    #[Test]
    public function user_with_view_access_can_view_mailbox()
    {
        $this->mailbox->users()->attach($this->user1, ['access' => MailboxPolicy::ACCESS_VIEW]);

        $this->actingAs($this->user1);

        $this->get(route('mailboxes.view', $this->mailbox))->assertStatus(200);
    }

    #[Test]
    public function user_without_view_access_cannot_view_mailbox()
    {
        $this->actingAs($this->user1);

        $this->get(route('mailboxes.view', $this->mailbox))->assertStatus(403);
    }

    #[Test]
    public function user_with_reply_access_can_reply()
    {
        $this->mailbox->users()->attach($this->user1, ['access' => MailboxPolicy::ACCESS_REPLY]);
        $this->user1->refresh();
        $this->assertTrue($this->user1->can('reply', $this->mailbox));
    }

    #[Test]
    public function user_with_view_access_cannot_reply()
    {
        $this->mailbox->users()->attach($this->user1, ['access' => MailboxPolicy::ACCESS_VIEW]);
        $this->user1->refresh();
        $this->assertFalse($this->user1->can('reply', $this->mailbox));
    }

    #[Test]
    public function user_with_admin_access_can_update_mailbox_settings()
    {
        $this->mailbox->users()->attach($this->user1, ['access' => MailboxPolicy::ACCESS_ADMIN]);
        $this->assertTrue($this->user1->can('update', $this->mailbox));
    }

    #[Test]
    public function user_with_reply_access_cannot_update_mailbox_settings()
    {
        $this->mailbox->users()->attach($this->user1, ['access' => MailboxPolicy::ACCESS_REPLY]);
        $this->assertFalse($this->user1->can('update', $this->mailbox));
    }
}
