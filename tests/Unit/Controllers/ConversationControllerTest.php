<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Http\Controllers\ConversationController;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ConversationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_controller_can_be_instantiated(): void
    {
        $controller = new ConversationController();

        $this->assertInstanceOf(ConversationController::class, $controller);
    }

    public function test_index_returns_view_for_authorized_user(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox->id);

        $request = Request::create('/mailboxes/' . $mailbox->id . '/conversations', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new ConversationController();
        $view = $controller->index($request, $mailbox);

        $this->assertEquals('conversations.index', $view->name());
    }

    public function test_index_aborts_for_unauthorized_user(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $request = Request::create('/mailboxes/' . $mailbox->id . '/conversations', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new ConversationController();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->index($request, $mailbox);
    }

    public function test_index_only_shows_published_conversations(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox->id);

        Conversation::factory()->create(['mailbox_id' => $mailbox->id, 'state' => 2]); // Published
        Conversation::factory()->create(['mailbox_id' => $mailbox->id, 'state' => 1]); // Draft

        $request = Request::create('/mailboxes/' . $mailbox->id . '/conversations', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new ConversationController();
        $view = $controller->index($request, $mailbox);

        $conversations = $view->getData()['conversations'];
        $this->assertCount(1, $conversations);
    }

    public function test_show_returns_view_for_authorized_user(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox->id);
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $request = Request::create('/conversations/' . $conversation->id, 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new ConversationController();
        $view = $controller->show($request, $conversation);

        $this->assertEquals('conversations.show', $view->name());
    }

    public function test_show_aborts_for_unauthorized_user(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $request = Request::create('/conversations/' . $conversation->id, 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new ConversationController();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->show($request, $conversation);
    }

    public function test_show_loads_conversation_relationships(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox->id);
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        Thread::factory()->create(['conversation_id' => $conversation->id, 'state' => 2]);

        $request = Request::create('/conversations/' . $conversation->id, 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new ConversationController();
        $view = $controller->show($request, $conversation);

        $loadedConversation = $view->getData()['conversation'];
        $this->assertTrue($loadedConversation->relationLoaded('mailbox'));
        $this->assertTrue($loadedConversation->relationLoaded('customer'));
        $this->assertTrue($loadedConversation->relationLoaded('threads'));
    }

    public function test_create_returns_view_for_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();

        $request = Request::create('/mailboxes/' . $mailbox->id . '/conversations/create', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new ConversationController();
        $view = $controller->create($request, $mailbox);

        $this->assertEquals('conversations.create', $view->name());
    }

    public function test_create_returns_view_for_authorized_user(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox->id);

        $request = Request::create('/mailboxes/' . $mailbox->id . '/conversations/create', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new ConversationController();
        $view = $controller->create($request, $mailbox);

        $this->assertEquals('conversations.create', $view->name());
    }

    public function test_create_aborts_for_unauthorized_user(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $request = Request::create('/mailboxes/' . $mailbox->id . '/conversations/create', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new ConversationController();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->create($request, $mailbox);
    }

    public function test_store_requires_subject_and_body(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox->id);
        $customer = Customer::factory()->create();

        $request = Request::create('/mailboxes/' . $mailbox->id . '/conversations', 'POST');
        $request->setUserResolver(fn () => $user);
        $request->merge([
            'customer_id' => $customer->id,
        ]);

        $controller = new ConversationController();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->store($request, $mailbox);
    }

    public function test_store_requires_valid_customer(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox->id);

        $request = Request::create('/mailboxes/' . $mailbox->id . '/conversations', 'POST');
        $request->setUserResolver(fn () => $user);
        $request->merge([
            'customer_id' => 99999, // Non-existent
            'subject' => 'Test',
            'body' => 'Test',
        ]);

        $controller = new ConversationController();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->store($request, $mailbox);
    }

    public function test_update_requires_authorization(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $request = Request::create('/conversations/' . $conversation->id, 'PUT');
        $request->setUserResolver(fn () => $user);
        $request->merge(['status' => 2]);

        $controller = new ConversationController();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->update($request, $conversation);
    }

    public function test_update_can_assign_conversation_to_user(): void
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox->id);
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $request = Request::create('/conversations/' . $conversation->id, 'PUT');
        $request->setUserResolver(fn () => $user);
        $request->merge(['user_id' => $assignee->id]);

        $controller = new ConversationController();
        $controller->update($request, $conversation);

        $this->assertEquals($assignee->id, $conversation->fresh()->user_id);
    }

    public function test_update_can_move_to_different_folder(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox->id);
        $folder1 = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        $folder2 = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id, 'folder_id' => $folder1->id]);

        $request = Request::create('/conversations/' . $conversation->id, 'PUT');
        $request->setUserResolver(fn () => $user);
        $request->merge(['folder_id' => $folder2->id]);

        $controller = new ConversationController();
        $controller->update($request, $conversation);

        $this->assertEquals($folder2->id, $conversation->fresh()->folder_id);
    }

    public function test_reply_adds_new_thread_to_conversation(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox->id);
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $request = Request::create('/conversations/' . $conversation->id . '/reply', 'POST');
        $request->setUserResolver(fn () => $user);
        $request->merge(['body' => 'This is a reply']);

        $controller = new ConversationController();
        $response = $controller->reply($request, $conversation);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals(1, Thread::where('conversation_id', $conversation->id)->count());
    }

    public function test_reply_requires_body(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox->id);
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $request = Request::create('/conversations/' . $conversation->id . '/reply', 'POST');
        $request->setUserResolver(fn () => $user);
        $request->merge([]);

        $controller = new ConversationController();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->reply($request, $conversation);
    }

    public function test_destroy_requires_mailbox_access(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $request = Request::create('/conversations/' . $conversation->id, 'DELETE');
        $request->setUserResolver(fn () => $user);

        $controller = new ConversationController();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->destroy($request, $conversation);
    }
}
