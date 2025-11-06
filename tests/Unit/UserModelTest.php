<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_admin_returns_true_for_admin_role(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertTrue($user->isAdmin());
    }

    public function test_user_is_admin_returns_false_for_regular_user(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $this->assertFalse($user->isAdmin());
    }

    public function test_user_is_active_returns_true_for_active_status(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->assertTrue($user->isActive());
    }

    public function test_user_is_active_returns_false_for_inactive_status(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_INACTIVE,
        ]);

        $this->assertFalse($user->isActive());
    }

    public function test_user_get_full_name_returns_first_and_last_name(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $user->getFullName());
    }

    public function test_user_get_full_name_returns_email_when_no_name(): void
    {
        $user = User::factory()->create([
            'first_name' => '',
            'last_name' => '',
            'email' => 'test@example.com',
        ]);

        $this->assertEquals('test@example.com', $user->getFullName());
    }

    public function test_user_name_accessor_returns_full_name(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $this->assertEquals('Jane Smith', $user->name);
    }

    public function test_user_has_mailboxes_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->mailboxes);
    }

    public function test_user_has_conversations_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->conversations);
    }
}
