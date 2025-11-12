<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Thread;
use App\Models\User;
use App\Policies\ThreadPolicy;
use Tests\UnitTestCase;

class ThreadPolicyTest extends UnitTestCase
{
    public function test_user_can_edit_own_message(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $thread = new Thread([
            'created_by_user_id' => $user->id,
            'type' => Thread::TYPE_MESSAGE,
        ]);
        $policy = new ThreadPolicy;

        $this->assertTrue($policy->edit($user, $thread));
    }

    public function test_user_can_edit_own_note(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $thread = new Thread([
            'created_by_user_id' => $user->id,
            'type' => Thread::TYPE_NOTE,
        ]);
        $policy = new ThreadPolicy;

        $this->assertTrue($policy->edit($user, $thread));
    }

    public function test_user_cannot_edit_other_user_message(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $otherUser = User::factory()->create(['role' => User::ROLE_USER]);
        $thread = new Thread([
            'created_by_user_id' => $otherUser->id,
            'type' => Thread::TYPE_MESSAGE,
        ]);
        $policy = new ThreadPolicy;

        $this->assertFalse($policy->edit($user, $thread));
    }

    public function test_admin_can_edit_any_message(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $otherUser = User::factory()->create(['role' => User::ROLE_USER]);
        $thread = new Thread([
            'created_by_user_id' => $otherUser->id,
            'type' => Thread::TYPE_MESSAGE,
        ]);
        $policy = new ThreadPolicy;

        $this->assertTrue($policy->edit($admin, $thread));
    }

    public function test_can_edit_customer_thread(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $thread = new Thread([
            'created_by_customer_id' => 1,
            'type' => Thread::TYPE_CUSTOMER,
        ]);
        $policy = new ThreadPolicy;

        $this->assertTrue($policy->edit($user, $thread));
    }

    public function test_user_can_delete_own_thread(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $thread = new Thread([
            'created_by_user_id' => $user->id,
            'type' => Thread::TYPE_MESSAGE,
        ]);
        $policy = new ThreadPolicy;

        $this->assertTrue($policy->delete($user, $thread));
    }

    public function test_user_cannot_delete_other_user_thread(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $otherUser = User::factory()->create(['role' => User::ROLE_USER]);
        $thread = new Thread([
            'created_by_user_id' => $otherUser->id,
            'type' => Thread::TYPE_MESSAGE,
        ]);
        $policy = new ThreadPolicy;

        $this->assertFalse($policy->delete($user, $thread));
    }
}
