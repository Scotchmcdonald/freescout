<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Thread;
use App\Models\User;
use App\Policies\ThreadPolicy;
use Mockery;
use Tests\PureUnitTestCase;

class ThreadPolicyTest extends PureUnitTestCase
{
    private function makeUser(int $id, bool $canManageTickets = false): User
    {
        /** @var User&\Mockery\MockInterface $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = $id;
        $user->shouldReceive('hasPermission')
            ->andReturnUsing(static fn (string $permission): bool => $permission === 'manage_tickets' && $canManageTickets);

        return $user;
    }

    public function test_user_can_edit_own_message(): void
    {
        $user = $this->makeUser(1);
        $thread = new Thread([
            'created_by_user_id' => 1,
            'type' => Thread::TYPE_MESSAGE,
        ]);
        $policy = new ThreadPolicy;

        $this->assertTrue($policy->edit($user, $thread));
    }

    public function test_user_can_edit_own_note(): void
    {
        $user = $this->makeUser(1);
        $thread = new Thread([
            'created_by_user_id' => 1,
            'type' => Thread::TYPE_NOTE,
        ]);
        $policy = new ThreadPolicy;

        $this->assertTrue($policy->edit($user, $thread));
    }

    public function test_user_cannot_edit_other_user_message(): void
    {
        $user = $this->makeUser(1);
        $thread = new Thread([
            'created_by_user_id' => 2,
            'type' => Thread::TYPE_MESSAGE,
        ]);
        $policy = new ThreadPolicy;

        $this->assertFalse($policy->edit($user, $thread));
    }

    public function test_manage_tickets_user_can_edit_any_message(): void
    {
        $manager = $this->makeUser(1, true);
        $thread = new Thread([
            'created_by_user_id' => 2,
            'type' => Thread::TYPE_MESSAGE,
        ]);
        $policy = new ThreadPolicy;

        $this->assertTrue($policy->edit($manager, $thread));
    }

    public function test_can_edit_customer_thread(): void
    {
        $user = $this->makeUser(1);
        $thread = new Thread([
            'created_by_customer_id' => 42,
            'type' => Thread::TYPE_CUSTOMER,
        ]);
        $policy = new ThreadPolicy;

        $this->assertTrue($policy->edit($user, $thread));
    }

    public function test_user_can_delete_own_thread(): void
    {
        $user = $this->makeUser(1);
        $thread = new Thread([
            'created_by_user_id' => 1,
            'type' => Thread::TYPE_MESSAGE,
        ]);
        $policy = new ThreadPolicy;

        $this->assertTrue($policy->delete($user, $thread));
    }

    public function test_user_cannot_delete_other_user_thread(): void
    {
        $user = $this->makeUser(1);
        $thread = new Thread([
            'created_by_user_id' => 2,
            'type' => Thread::TYPE_MESSAGE,
        ]);
        $policy = new ThreadPolicy;

        $this->assertFalse($policy->delete($user, $thread));
    }
}