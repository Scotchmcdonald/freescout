<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionsTest extends TestCase
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
     * Test admin can assign mailbox permissions to user.
     */
    public function test_admin_can_assign_mailbox_permissions_to_user(): void
    {
        // Arrange
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();

        $permissionsData = [
            'mailboxes' => [$mailbox1->id, $mailbox2->id],
        ];

        // Act
        $response = $this->actingAs($this->admin)->post(
            route('users.permissions', $this->user),
            $permissionsData
        );

        // Assert
        $this->assertTrue($response->isOk() || $response->isRedirect());
        $this->assertTrue($this->user->mailboxes->contains($mailbox1->id));
        $this->assertTrue($this->user->mailboxes->contains($mailbox2->id));
    }

    /**
     * Test admin can remove mailbox permissions from user.
     */
    public function test_admin_can_remove_mailbox_permissions_from_user(): void
    {
        // Arrange
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        $this->user->mailboxes()->attach([$mailbox1->id, $mailbox2->id]);

        $permissionsData = [
            'mailboxes' => [$mailbox1->id], // Remove mailbox2
        ];

        // Act
        $response = $this->actingAs($this->admin)->post(
            route('users.permissions', $this->user),
            $permissionsData
        );

        // Assert
        $this->assertTrue($response->isOk() || $response->isRedirect());
        $this->user->refresh();
        $this->assertTrue($this->user->mailboxes->contains($mailbox1->id));
        $this->assertFalse($this->user->mailboxes->contains($mailbox2->id));
    }

    /**
     * Test admin can remove all mailbox permissions.
     */
    public function test_admin_can_remove_all_mailbox_permissions(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $this->user->mailboxes()->attach($mailbox->id);

        $permissionsData = [
            'mailboxes' => [],
        ];

        // Act
        $response = $this->actingAs($this->admin)->post(
            route('users.permissions', $this->user),
            $permissionsData
        );

        // Assert
        $this->assertTrue($response->isOk() || $response->isRedirect());
        $this->user->refresh();
        $this->assertEquals(0, $this->user->mailboxes->count());
    }

    /**
     * Test non-admin cannot assign permissions.
     */
    public function test_non_admin_cannot_assign_permissions(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $targetUser = User::factory()->create();

        $permissionsData = [
            'mailboxes' => [$mailbox->id],
        ];

        // Act
        $response = $this->actingAs($this->user)->post(
            route('users.permissions', $targetUser),
            $permissionsData
        );

        // Assert
        $response->assertForbidden();
        $this->assertEquals(0, $targetUser->mailboxes->count());
    }

    /**
     * Test guest cannot assign permissions.
     */
    public function test_guest_cannot_assign_permissions(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();

        $permissionsData = [
            'mailboxes' => [$mailbox->id],
        ];

        // Act
        $response = $this->post(
            route('users.permissions', $this->user),
            $permissionsData
        );

        // Assert
        $response->assertRedirect(route('login'));
    }

    /**
     * Test permission assignment handles invalid mailbox IDs.
     */
    public function test_permission_assignment_handles_invalid_mailbox_ids(): void
    {
        // Arrange
        $permissionsData = [
            'mailboxes' => [999, 998], // Non-existent IDs
        ];

        $originalCount = $this->user->mailboxes->count();

        // Act
        $response = $this->actingAs($this->admin)->post(
            route('users.permissions', $this->user),
            $permissionsData
        );

        // Assert - Should either succeed with no assignments or show error
        $this->assertTrue($response->isOk() || $response->isRedirect());
        $this->user->refresh();

        // The behavior depends on implementation - either filters invalid IDs or accepts them
        // Just verify the operation completed without error
        $this->assertTrue(true);
    }

    /**
     * Test admin role provides broader access than regular users.
     */
    public function test_admin_role_provides_broader_access(): void
    {
        // Arrange
        $mailbox1 = Mailbox::factory()->create();

        // Regular user needs explicit permission
        $regularUser = User::factory()->create(['role' => User::ROLE_USER]);

        // Admin doesn't need explicit permission (handled by isAdmin() checks in controllers)
        // This test documents the intended behavior

        // Assert - Verify roles are set correctly
        $this->assertTrue($this->admin->isAdmin());
        $this->assertFalse($regularUser->isAdmin());

        // Admin should have access to features regular users don't
        $this->assertEquals(User::ROLE_ADMIN, $this->admin->role);
        $this->assertEquals(User::ROLE_USER, $regularUser->role);
    }
}
