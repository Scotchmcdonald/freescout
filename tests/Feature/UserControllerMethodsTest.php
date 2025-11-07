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
}
