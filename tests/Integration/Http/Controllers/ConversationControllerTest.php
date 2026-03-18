<?php

declare(strict_types=1);

namespace Tests\Integration\Http\Controllers;

use App\Http\Controllers\ConversationController;
use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Test ConversationController basic functionality
 *
 * Focus: Access control, relationship loading, basic CRUD
 */
class ConversationControllerTest extends TestCase
{
    use RefreshDatabase;

    private ConversationController $controller;
    private User $user;
    private Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new ConversationController;
        $this->user = User::factory()->create();
        $this->mailbox = Mailbox::factory()->create();

        // Attach user to mailbox
        $this->user->mailboxes()->attach($this->mailbox->id);
    }

    public function test_index_returns_view_with_conversations(): void
    {
        Conversation::factory()->count(5)->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 2, // Published
        ]);

        $request = Request::create('/mailbox/'.$this->mailbox->id.'/conversations');
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->index($request, $this->mailbox);

        $this->assertNotNull($response);
        $this->assertEquals('conversations.index', $response->name());
    }

    public function test_index_only_shows_published_conversations(): void
    {
        // Create published and draft conversations
        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 2, // Published
        ]);

        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 1, // Draft
        ]);

        $request = Request::create('/mailbox/'.$this->mailbox->id.'/conversations');
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->index($request, $this->mailbox);
        $conversations = $response->getData()['conversations'];

        $this->assertCount(1, $conversations);
    }

    public function test_index_orders_by_last_reply_at_desc(): void
    {
        $older = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 2,
            'last_reply_at' => now()->subHours(2),
        ]);

        $newer = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 2,
            'last_reply_at' => now()->subHour(),
        ]);

        $request = Request::create('/mailbox/'.$this->mailbox->id.'/conversations');
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->index($request, $this->mailbox);
        $conversations = $response->getData()['conversations'];

        $this->assertEquals($newer->id, $conversations->first()->id);
    }

    public function test_index_denies_access_to_unauthorized_user(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $unauthorizedUser = User::factory()->create();

        $request = Request::create('/mailbox/'.$this->mailbox->id.'/conversations');
        $request->setUserResolver(fn () => $unauthorizedUser);

        try {
            $this->controller->index($request, $this->mailbox);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
            throw $e;
        }
    }

    public function test_show_returns_view_with_conversation(): void
    {
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 2,
        ]);

        $request = Request::create('/conversations/'.$conversation->id);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->show($request, $conversation);

        $this->assertNotNull($response);
        $this->assertEquals('conversations.show', $response->name());
    }

    public function test_show_loads_required_relationships(): void
    {
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 2,
        ]);

        Thread::factory()->count(3)->create([
            'conversation_id' => $conversation->id,
            'state' => 2,
        ]);

        $request = Request::create('/conversations/'.$conversation->id);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->show($request, $conversation);
        $data = $response->getData();

        $this->assertTrue($data['conversation']->relationLoaded('mailbox'));
        $this->assertTrue($data['conversation']->relationLoaded('customer'));
        $this->assertTrue($data['conversation']->relationLoaded('threads'));
    }

    public function test_show_only_loads_published_threads(): void
    {
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 2,
        ]);

        Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => 2, // Published
        ]);

        Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => 1, // Draft
        ]);

        $request = Request::create('/conversations/'.$conversation->id);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->show($request, $conversation);
        $conv = $response->getData()['conversation'];

        $this->assertCount(1, $conv->threads);
    }

    public function test_show_denies_access_to_unauthorized_user(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
        ]);

        $unauthorizedUser = User::factory()->create();

        $request = Request::create('/conversations/'.$conversation->id);
        $request->setUserResolver(fn () => $unauthorizedUser);

        try {
            $this->controller->show($request, $conversation);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
            throw $e;
        }
    }

    public function test_show_marks_notifications_as_read(): void
    {
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'state' => 2,
        ]);

        $notificationId = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'data' => json_encode(['conversation_id' => $conversation->id], JSON_THROW_ON_ERROR),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/conversations/'.$conversation->id);
        $request->setUserResolver(fn () => $this->user);

        $this->controller->show($request, $conversation);

        $readAt = DB::table('notifications')
            ->where('id', $notificationId)
            ->value('read_at');

        $this->assertNotNull($readAt);
    }

    public function test_create_returns_view_for_authorized_user(): void
    {
        $request = Request::create('/mailbox/'.$this->mailbox->id.'/conversations/create');
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->create($request, $this->mailbox);

        $this->assertNotNull($response);
        $this->assertEquals('conversations.create', $response->name());
    }

    public function test_create_allows_admin_access_to_any_mailbox(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $otherMailbox = Mailbox::factory()->create();

        $request = Request::create('/mailbox/'.$otherMailbox->id.'/conversations/create');
        $request->setUserResolver(fn () => $admin);

        $response = $this->controller->create($request, $otherMailbox);

        $this->assertNotNull($response);
    }

    public function test_create_denies_access_to_unauthorized_user(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $unauthorizedUser = User::factory()->create();

        $request = Request::create('/mailbox/'.$this->mailbox->id.'/conversations/create');
        $request->setUserResolver(fn () => $unauthorizedUser);

        try {
            $this->controller->create($request, $this->mailbox);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
            throw $e;
        }
    }

    public function test_create_loads_folders_for_mailbox(): void
    {
        Folder::factory()->count(3)->create([
            'mailbox_id' => $this->mailbox->id,
        ]);

        $request = Request::create('/mailbox/'.$this->mailbox->id.'/conversations/create');
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->create($request, $this->mailbox);
        $folders = $response->getData()['folders'];

        $this->assertGreaterThanOrEqual(3, $folders->count());
    }
}
