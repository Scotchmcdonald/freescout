<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Events\ConversationUserChanged;
use App\Events\CustomerCreatedConversation;
use App\Events\CustomerReplied;
use App\Events\UserAddedNote;
use App\Events\UserCreatedConversation;
use App\Events\UserDeleted;
use App\Events\UserReplied;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class EventsTest extends UnitTestCase
{

    // ConversationUserChanged Tests

    #[Test]
    public function conversation_user_changed_can_be_instantiated(): void
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();

        $event = new ConversationUserChanged($conversation, $user);

        $this->assertInstanceOf(ConversationUserChanged::class, $event);
        $this->assertEquals($conversation->id, $event->conversation->id);
        $this->assertEquals($user->id, $event->user->id);
    }

    #[Test]
    public function conversation_user_changed_can_be_dispatched(): void
    {
        Event::fake();

        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();

        ConversationUserChanged::dispatch($conversation, $user);

        Event::assertDispatched(ConversationUserChanged::class);
    }

    #[Test]
    public function conversation_user_changed_has_dispatchable_trait(): void
    {
        $event = new ConversationUserChanged(
            Conversation::factory()->make(),
            User::factory()->make()
        );

        $this->assertTrue(method_exists($event, 'dispatch'));
    }

    // UserAddedNote Tests

    #[Test]
    public function user_added_note_can_be_instantiated(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new UserAddedNote($conversation, $thread);

        $this->assertInstanceOf(UserAddedNote::class, $event);
        $this->assertEquals($conversation->id, $event->conversation->id);
        $this->assertEquals($thread->id, $event->thread->id);
    }

    #[Test]
    public function user_added_note_can_be_dispatched(): void
    {
        Event::fake();

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        UserAddedNote::dispatch($conversation, $thread);

        Event::assertDispatched(UserAddedNote::class);
    }

    #[Test]
    public function user_added_note_has_correct_properties(): void
    {
        $conversation = Conversation::factory()->create(['subject' => 'Test Subject']);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_NOTE,
        ]);

        $event = new UserAddedNote($conversation, $thread);

        $this->assertEquals('Test Subject', $event->conversation->subject);
        $this->assertEquals(Thread::TYPE_NOTE, $event->thread->type);
    }

    // UserCreatedConversation Tests

    #[Test]
    public function user_created_conversation_can_be_instantiated(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new UserCreatedConversation($conversation, $thread);

        $this->assertInstanceOf(UserCreatedConversation::class, $event);
        $this->assertEquals($conversation->id, $event->conversation->id);
        $this->assertEquals($thread->id, $event->thread->id);
    }

    #[Test]
    public function user_created_conversation_can_be_dispatched(): void
    {
        Event::fake();

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        UserCreatedConversation::dispatch($conversation, $thread);

        Event::assertDispatched(UserCreatedConversation::class);
    }

    #[Test]
    public function user_created_conversation_stores_models(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['created_by_user_id' => $user->id]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new UserCreatedConversation($conversation, $thread);

        $this->assertInstanceOf(Conversation::class, $event->conversation);
        $this->assertInstanceOf(Thread::class, $event->thread);
    }

    // UserDeleted Tests

    #[Test]
    public function user_deleted_can_be_instantiated(): void
    {
        $deletedUser = User::factory()->create();
        $byUser = User::factory()->create();

        $event = new UserDeleted($deletedUser, $byUser);

        $this->assertInstanceOf(UserDeleted::class, $event);
        $this->assertEquals($deletedUser->id, $event->deleted_user->id);
        $this->assertEquals($byUser->id, $event->by_user->id);
    }

    #[Test]
    public function user_deleted_can_be_dispatched(): void
    {
        Event::fake();

        $deletedUser = User::factory()->create();
        $byUser = User::factory()->create();

        UserDeleted::dispatch($deletedUser, $byUser);

        Event::assertDispatched(UserDeleted::class);
    }

    #[Test]
    public function user_deleted_tracks_both_users(): void
    {
        $deletedUser = User::factory()->create(['email' => 'deleted@example.com']);
        $byUser = User::factory()->create(['email' => 'admin@example.com']);

        $event = new UserDeleted($deletedUser, $byUser);

        $this->assertEquals('deleted@example.com', $event->deleted_user->email);
        $this->assertEquals('admin@example.com', $event->by_user->email);
    }

    // UserReplied Tests

    #[Test]
    public function user_replied_can_be_instantiated(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new UserReplied($conversation, $thread);

        $this->assertInstanceOf(UserReplied::class, $event);
        $this->assertEquals($conversation->id, $event->conversation->id);
        $this->assertEquals($thread->id, $event->thread->id);
    }

    #[Test]
    public function user_replied_can_be_dispatched(): void
    {
        Event::fake();

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        UserReplied::dispatch($conversation, $thread);

        Event::assertDispatched(UserReplied::class);
    }

    #[Test]
    public function user_replied_has_dispatchable_trait(): void
    {
        $event = new UserReplied(
            Conversation::factory()->make(),
            Thread::factory()->make()
        );

        $this->assertTrue(method_exists($event, 'dispatch'));
    }

    // CustomerCreatedConversation Tests

    #[Test]
    public function customer_created_conversation_can_be_instantiated(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $customer = Customer::factory()->create();

        $event = new CustomerCreatedConversation($conversation, $thread, $customer);

        $this->assertInstanceOf(CustomerCreatedConversation::class, $event);
        $this->assertEquals($conversation->id, $event->conversation->id);
        $this->assertEquals($thread->id, $event->thread->id);
        $this->assertEquals($customer->id, $event->customer->id);
    }

    #[Test]
    public function customer_created_conversation_can_be_dispatched(): void
    {
        Event::fake();

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $customer = Customer::factory()->create();

        CustomerCreatedConversation::dispatch($conversation, $thread, $customer);

        Event::assertDispatched(CustomerCreatedConversation::class);
    }

    // CustomerReplied Tests

    #[Test]
    public function customer_replied_can_be_instantiated(): void
    {
        $conversation = Conversation::factory()->create();
        $customer = Customer::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $event = new CustomerReplied($conversation, $thread, $customer);

        $this->assertInstanceOf(CustomerReplied::class, $event);
        $this->assertEquals($conversation->id, $event->conversation->id);
        $this->assertEquals($thread->id, $event->thread->id);
        $this->assertEquals($customer->id, $event->customer->id);
    }

    #[Test]
    public function customer_replied_can_be_dispatched(): void
    {
        Event::fake();

        $conversation = Conversation::factory()->create();
        $customer = Customer::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        CustomerReplied::dispatch($conversation, $thread, $customer);

        Event::assertDispatched(CustomerReplied::class);
    }

    #[Test]
    public function all_events_use_dispatchable_trait(): void
    {
        $conversation = Conversation::factory()->make();
        $thread = Thread::factory()->make();
        $user = User::factory()->make();
        $customer = Customer::factory()->make();

        $events = [
            new ConversationUserChanged($conversation, $user),
            new UserAddedNote($conversation, $thread),
            new UserCreatedConversation($conversation, $thread),
            new UserDeleted($user, $user),
            new UserReplied($conversation, $thread),
            new CustomerCreatedConversation($conversation, $thread, $customer),
            new CustomerReplied($conversation, $thread, $customer),
        ];

        foreach ($events as $event) {
            $this->assertTrue(method_exists($event, 'dispatch'));
        }
    }

    #[Test]
    public function events_can_be_listened_to(): void
    {
        Event::fake();

        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        ConversationUserChanged::dispatch($conversation, $user);
        UserAddedNote::dispatch($conversation, $thread);
        UserCreatedConversation::dispatch($conversation, $thread);
        UserDeleted::dispatch($user, $user);
        UserReplied::dispatch($conversation, $thread);
        CustomerCreatedConversation::dispatch($conversation, $thread, $customer);
        CustomerReplied::dispatch($conversation, $thread, $customer);

        Event::assertDispatched(ConversationUserChanged::class);
        Event::assertDispatched(UserAddedNote::class);
        Event::assertDispatched(UserCreatedConversation::class);
        Event::assertDispatched(UserDeleted::class);
        Event::assertDispatched(UserReplied::class);
        Event::assertDispatched(CustomerCreatedConversation::class);
        Event::assertDispatched(CustomerReplied::class);
    }
}
