<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationCreateFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mailbox = Mailbox::factory()->create([
            'name' => 'Support',
        ]);

        $this->mailbox->users()->attach($this->user);

        // Create folders
        Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);
    }

    /** @test */
    public function admin_can_view_create_conversation_form(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->get(route('conversations.create', $this->mailbox));

        // Assert
        $response->assertOk();
        $response->assertViewIs('conversations.create');
        $response->assertViewHas('mailbox');
        $response->assertViewHas('folders');
    }

    /** @test */
    public function regular_user_with_mailbox_access_can_view_create_form(): void
    {
        // Arrange
        $regularUser = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);
        $this->mailbox->users()->attach($regularUser);

        // Act
        $response = $this->actingAs($regularUser)
            ->get(route('conversations.create', $this->mailbox));

        // Assert
        $response->assertOk();
    }

    /** @test */
    public function user_without_mailbox_access_cannot_view_create_form(): void
    {
        // Arrange
        $regularUser = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        // Act
        $response = $this->actingAs($regularUser)
            ->get(route('conversations.create', $this->mailbox));

        // Assert
        $response->assertForbidden();
    }

    /** @test */
    public function unauthenticated_user_cannot_view_create_form(): void
    {
        // Act
        $response = $this->get(route('conversations.create', $this->mailbox));

        // Assert
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function create_form_includes_user_accessible_folders_only(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $this->mailbox->users()->attach($user1);

        // Create a personal folder for user1
        $personalFolder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'user_id' => $user1->id,
            'name' => 'My Personal Folder',
        ]);

        // Create a shared folder (no user_id)
        $sharedFolder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'user_id' => null,
            'name' => 'Shared Folder',
        ]);

        // Act - user1 views the form
        $response = $this->actingAs($user1)
            ->get(route('conversations.create', $this->mailbox));

        // Assert - should see both personal and shared folders
        $folders = $response->viewData('folders');
        $this->assertTrue($folders->contains($personalFolder));
        $this->assertTrue($folders->contains($sharedFolder));

        // Act - admin views the form
        $response = $this->actingAs($this->user)
            ->get(route('conversations.create', $this->mailbox));

        // Assert - admin sees shared folder but not user1's personal folder
        $folders = $response->viewData('folders');
        $this->assertTrue($folders->contains($sharedFolder));
        $this->assertFalse($folders->contains($personalFolder));
    }
}
