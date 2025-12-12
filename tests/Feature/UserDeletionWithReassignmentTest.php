<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\UserController;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversMethod;
use Tests\TestCase;

/**
 * Tests for enhanced user deletion with conversation reassignment.
 */
#[CoversMethod(UserController::class, 'destroy')]
class UserDeletionWithReassignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $userToDelete;
    protected User $targetUser;
    protected Mailbox $mailbox;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->userToDelete = User::factory()->create(['role' => User::ROLE_USER]);
        $this->targetUser = User::factory()->create(['role' => User::ROLE_USER]);
        $this->mailbox = Mailbox::factory()->create();
        $this->customer = Customer::factory()->create();
    }

    // ====================
    // BASIC DELETION TESTS
    // ====================

    public function test_can_delete_user_without_conversations(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('users.destroy', $this->userToDelete));

        $response->assertRedirect(route('users.index'));
        
        $this->userToDelete->refresh();
        $this->assertEquals(User::STATUS_DELETED, $this->userToDelete->status);
    }

    public function test_cannot_delete_user_with_conversations_without_reassign(): void
    {
        // Create a conversation assigned to the user
        Conversation::factory()->create([
            'user_id' => $this->userToDelete->id,
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('users.destroy', $this->userToDelete));

        $response->assertSessionHasErrors('error');
        
        $this->userToDelete->refresh();
        $this->assertNotEquals(User::STATUS_DELETED, $this->userToDelete->status);
    }

    // ====================
    // REASSIGNMENT TESTS
    // ====================

    public function test_delete_user_with_conversation_reassignment(): void
    {
        // Create conversations assigned to the user
        $conversation1 = Conversation::factory()->create([
            'user_id' => $this->userToDelete->id,
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
        ]);
        $conversation2 = Conversation::factory()->create([
            'user_id' => $this->userToDelete->id,
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('users.destroy', $this->userToDelete), [
                'reassign_to' => $this->targetUser->id,
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');
        
        // Verify user is deleted
        $this->userToDelete->refresh();
        $this->assertEquals(User::STATUS_DELETED, $this->userToDelete->status);
        
        // Verify conversations are reassigned
        $conversation1->refresh();
        $conversation2->refresh();
        $this->assertEquals($this->targetUser->id, $conversation1->user_id);
        $this->assertEquals($this->targetUser->id, $conversation2->user_id);
    }

    public function test_cannot_reassign_to_self(): void
    {
        Conversation::factory()->create([
            'user_id' => $this->userToDelete->id,
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('users.destroy', $this->userToDelete), [
                'reassign_to' => $this->userToDelete->id,
            ]);

        $response->assertSessionHasErrors('error');
    }

    public function test_cannot_reassign_to_deleted_user(): void
    {
        $deletedUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'status' => User::STATUS_DELETED,
        ]);
        
        Conversation::factory()->create([
            'user_id' => $this->userToDelete->id,
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('users.destroy', $this->userToDelete), [
                'reassign_to' => $deletedUser->id,
            ]);

        $response->assertSessionHasErrors('error');
    }

    public function test_cannot_reassign_to_nonexistent_user(): void
    {
        Conversation::factory()->create([
            'user_id' => $this->userToDelete->id,
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('users.destroy', $this->userToDelete), [
                'reassign_to' => 9999,
            ]);

        $response->assertSessionHasErrors('error');
    }

    // ====================
    // AUTHORIZATION TESTS
    // ====================

    public function test_non_admin_cannot_delete_user(): void
    {
        $this->actingAs($this->targetUser)
            ->delete(route('users.destroy', $this->userToDelete))
            ->assertForbidden();
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->delete(route('users.destroy', $this->adminUser));

        // This should fail because of policy
        $response->assertForbidden();
    }
}
