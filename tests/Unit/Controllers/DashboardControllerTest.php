<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Http\Controllers\DashboardController;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\UnitTestCase;

class DashboardControllerTest extends UnitTestCase
{

    public function test_controller_can_be_instantiated(): void
    {
        $controller = new DashboardController;

        $this->assertInstanceOf(DashboardController::class, $controller);
    }

    public function test_index_returns_view_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $admin);

        $controller = new DashboardController;
        $view = $controller->index($request);

        $this->assertEquals('dashboard', $view->name());
    }

    public function test_index_returns_view_for_regular_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new DashboardController;
        $view = $controller->index($request);

        $this->assertEquals('dashboard', $view->name());
    }

    public function test_index_passes_user_to_view(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new DashboardController;
        $view = $controller->index($request);

        $this->assertArrayHasKey('user', $view->getData());
        $this->assertEquals($user->id, $view->getData()['user']->id);
    }

    public function test_index_passes_mailboxes_to_view(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new DashboardController;
        $view = $controller->index($request);

        $this->assertArrayHasKey('mailboxes', $view->getData());
        $this->assertCount(1, $view->getData()['mailboxes']);
    }

    public function test_admin_sees_all_mailboxes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Mailbox::factory()->count(3)->create();

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $admin);

        $controller = new DashboardController;
        $view = $controller->index($request);

        $this->assertCount(3, $view->getData()['mailboxes']);
    }

    public function test_regular_user_sees_only_assigned_mailboxes(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $assignedMailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($assignedMailbox);

        // Create additional mailboxes the user shouldn't see
        Mailbox::factory()->count(2)->create();

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new DashboardController;
        $view = $controller->index($request);

        $this->assertCount(1, $view->getData()['mailboxes']);
    }

    public function test_index_counts_active_conversations(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);

        // Create active conversations
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => 1,
            'state' => 2,
        ]);
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => 1,
            'state' => 2,
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new DashboardController;
        $view = $controller->index($request);

        $this->assertEquals(2, $view->getData()['activeConversations']);
    }

    public function test_index_counts_unassigned_conversations(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);

        // Create unassigned active conversations
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => null,
            'status' => 1,
            'state' => 2,
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new DashboardController;
        $view = $controller->index($request);

        $this->assertEquals(1, $view->getData()['unassignedConversations']);
    }

    public function test_index_provides_stats_per_mailbox(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);

        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => 1,
            'state' => 2,
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new DashboardController;
        $view = $controller->index($request);

        $stats = $view->getData()['stats'];
        $this->assertArrayHasKey($mailbox->id, $stats);
        $this->assertArrayHasKey('active', $stats[$mailbox->id]);
        $this->assertArrayHasKey('unassigned', $stats[$mailbox->id]);
    }

    public function test_index_excludes_closed_conversations_from_count(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);

        // Create active conversation
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => 1,
            'state' => 2,
        ]);

        // Create closed conversation (shouldn't be counted)
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => 3, // Closed status
            'state' => 2,
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new DashboardController;
        $view = $controller->index($request);

        $this->assertEquals(1, $view->getData()['activeConversations']);
    }

    public function test_index_excludes_draft_conversations_from_count(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);

        // Create published conversation
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => 1,
            'state' => 2, // Published
        ]);

        // Create draft conversation (shouldn't be counted)
        Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => 1,
            'state' => 1, // Draft
        ]);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new DashboardController;
        $view = $controller->index($request);

        $this->assertEquals(1, $view->getData()['activeConversations']);
    }
}
