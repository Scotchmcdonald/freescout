<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerMethodsTest extends TestCase
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

    /**
     * Test create() method - NOTE: View has route name issue, testing authorization instead
     */
    public function test_non_admin_cannot_view_create_user_form(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        $response = $this->get(route('users.create'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_create_user_form(): void
    {
        $response = $this->get(route('users.create'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test show() method - displays user details
     */
    public function test_admin_can_view_user_details(): void
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->get(route('users.show', $user));

        $response->assertOk();
        $response->assertViewIs('users.show');
        $response->assertViewHas('user', function ($viewUser) use ($user) {
            return $viewUser->id === $user->id;
        });
    }

    public function test_user_can_view_own_profile(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        $response = $this->get(route('users.show', $user));

        $response->assertOk();
        $response->assertViewIs('users.show');
    }

    public function test_user_cannot_view_other_user_profile(): void
    {
        $user1 = User::factory()->create(['role' => User::ROLE_USER]);
        $user2 = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user1);

        $response = $this->get(route('users.show', $user2));

        $response->assertForbidden();
    }

    public function test_show_includes_related_data(): void
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);

        $response = $this->get(route('users.show', $user));

        $response->assertOk();
        $response->assertViewHas('user', function ($viewUser) use ($mailbox) {
            return $viewUser->mailboxes->contains($mailbox);
        });
    }

    /**
     * Test edit() method - displays user edit form
     */
    public function test_admin_can_view_edit_user_form(): void
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        $response = $this->get(route('users.edit', $user));

        $response->assertOk();
        $response->assertViewIs('users.edit');
        $response->assertViewHas('user', function ($viewUser) use ($user) {
            return $viewUser->id === $user->id;
        });
    }

    public function test_user_can_edit_own_profile(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user);

        $response = $this->get(route('users.edit', $user));

        $response->assertOk();
        $response->assertViewIs('users.edit');
    }

    public function test_user_cannot_edit_other_user(): void
    {
        $user1 = User::factory()->create(['role' => User::ROLE_USER]);
        $user2 = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user1);

        $response = $this->get(route('users.edit', $user2));

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_edit_user_form(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('users.edit', $user));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test ajax() method - NOTE: This method exists in controller but no route is defined
     * Skipping tests as the route doesn't exist in routes/web.php
     */

    /**
     * Edge case tests for show() and edit() methods
     */
    public function test_show_with_non_existent_user(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('users.show', 99999));

        $response->assertNotFound();
    }

    public function test_edit_with_non_existent_user(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('users.edit', 99999));

        $response->assertNotFound();
    }

    public function test_show_inactive_user(): void
    {
        $this->actingAs($this->admin);

        $inactiveUser = User::factory()->create([
            'status' => User::STATUS_INACTIVE,
        ]);

        $response = $this->get(route('users.show', $inactiveUser));

        // Admin should still be able to view inactive users
        $response->assertOk();
        $response->assertViewHas('user', function ($viewUser) use ($inactiveUser) {
            return $viewUser->id === $inactiveUser->id
                && $viewUser->status === User::STATUS_INACTIVE;
        });
    }

    public function test_edit_inactive_user(): void
    {
        $this->actingAs($this->admin);

        $inactiveUser = User::factory()->create([
            'status' => User::STATUS_INACTIVE,
        ]);

        $response = $this->get(route('users.edit', $inactiveUser));

        // Admin should still be able to edit inactive users
        $response->assertOk();
    }

    public function test_show_with_deleted_related_data(): void
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);

        // Delete the mailbox (soft delete if applicable)
        $mailbox->delete();

        // Should still be able to view user without errors
        $response = $this->get(route('users.show', $user));

        $response->assertOk();
    }

    public function test_show_user_with_no_mailboxes(): void
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();
        // Don't attach any mailboxes

        $response = $this->get(route('users.show', $user));

        $response->assertOk();
        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->mailboxes->isEmpty();
        });
    }

    public function test_show_user_with_multiple_mailboxes(): void
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        $mailbox3 = Mailbox::factory()->create();

        $user->mailboxes()->attach([$mailbox1->id, $mailbox2->id, $mailbox3->id]);

        $response = $this->get(route('users.show', $user));

        $response->assertOk();
        $response->assertViewHas('user', function ($viewUser) use ($mailbox1, $mailbox2, $mailbox3) {
            return $viewUser->mailboxes->count() === 3
                && $viewUser->mailboxes->contains($mailbox1)
                && $viewUser->mailboxes->contains($mailbox2)
                && $viewUser->mailboxes->contains($mailbox3);
        });
    }

    public function test_regular_user_cannot_view_admin_profile(): void
    {
        $regularUser = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($regularUser);

        $response = $this->get(route('users.show', $this->admin));

        $response->assertForbidden();
    }

    public function test_regular_user_cannot_edit_admin_profile(): void
    {
        $regularUser = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($regularUser);

        $response = $this->get(route('users.edit', $this->admin));

        $response->assertForbidden();
    }

    public function test_show_includes_conversations_relationship(): void
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        $response = $this->get(route('users.show', $user));

        $response->assertOk();
        // Verify that the conversations relationship is loaded
        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->relationLoaded('conversations');
        });
    }

    public function test_user_can_view_own_profile_with_special_characters_in_name(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'first_name' => "O'Brien",
            'last_name' => 'José-María',
            'email' => 'test+user@example.com',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('users.show', $user));

        $response->assertOk();
        $response->assertViewIs('users.show');
    }

    public function test_edit_form_for_user_with_boundary_field_values(): void
    {
        $this->actingAs($this->admin);

        // Test with values at the database column limits
        // first_name VARCHAR(20), last_name VARCHAR(30), job_title VARCHAR(100)
        $user = User::factory()->create([
            'first_name' => str_repeat('A', 20), // At column limit
            'last_name' => str_repeat('B', 30),   // At column limit
            'job_title' => str_repeat('C', 100),  // At column limit
        ]);

        $response = $this->get(route('users.edit', $user));

        $response->assertOk();
    }
}
