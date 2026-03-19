<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Events\CustomerCreatedConversation;
use App\Events\NewMessageReceived;
use App\Events\UserCreatedConversation;
use App\Events\UserReplied;
use App\Jobs\SendAutoReplyJob;
use App\Jobs\SendConversationReplyJob;
use App\Jobs\SendNotificationToUsersJob;
use App\Listeners\HandleNewMessage;
use App\Listeners\SendAutoReply;
use App\Listeners\SendNotificationToUsers;
use App\Listeners\SendPasswordChanged;
use App\Listeners\SendReplyToCustomer;
use App\Listeners\UpdateMailboxCounters;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\UnitTestCase;

class ListenersComprehensiveTest extends UnitTestCase
{
    /**
     * @return array<int, int>
     */
    private function queuedUserIds(SendNotificationToUsersJob $job): array
    {
        /** @var array<int, int> $ids */
        $ids = $job->users->pluck('id')->sort()->values()->all();

        return $ids;
    }

    public function test_update_mailbox_counters_calls_mailbox_updater_when_available(): void
    {
        $mailbox = new class extends Mailbox
        {
            public bool $updated = false;

            public function updateFoldersCounters(): void
            {
                $this->updated = true;
            }
        };

        $conversation = Conversation::factory()->make();
        $conversation->setRelation('mailbox', $mailbox);

        (new UpdateMailboxCounters)->handle(
            new ConversationStatusChanged($conversation, null, Conversation::STATUS_ACTIVE, Conversation::STATUS_CLOSED)
        );

        $this->assertTrue($mailbox->updated);
    }

    public function test_conversation_user_changed_notifications_exclude_the_assigning_user(): void
    {
        Queue::fake();

        $assigningUser = User::factory()->create();
        $assignedUser = User::factory()->create();
        $watcher = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach([$assigningUser->id, $assignedUser->id, $watcher->id]);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $assignedUser->id,
        ]);

        Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        (new SendNotificationToUsers)->handle(
            new ConversationUserChanged($conversation, null, $assignedUser, $assigningUser)
        );

        Queue::assertPushed(SendNotificationToUsersJob::class, function (SendNotificationToUsersJob $job) use ($assignedUser, $conversation, $watcher): bool {
            return $job->conversation->is($conversation)
                && $this->queuedUserIds($job) === [$assignedUser->id, $watcher->id];
        });
    }

    public function test_user_replied_notifications_ignore_imported_threads(): void
    {
        Queue::fake();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_by_user_id' => User::factory()->create()->id,
            'imported' => true,
        ]);

        (new SendNotificationToUsers)->handle(new UserReplied($conversation, $thread));

        Queue::assertNothingPushed();
    }

    public function test_send_reply_to_customer_queues_email_job_on_the_emails_queue(): void
    {
        Queue::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => 'customer@example.com',
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'imported' => false,
            'type' => Thread::TYPE_MESSAGE,
        ]);

        (new SendReplyToCustomer)->handle(new UserCreatedConversation($conversation, $thread));

        Queue::assertPushed(SendConversationReplyJob::class, function (SendConversationReplyJob $job) use ($conversation, $thread): bool {
            return $job->conversation->is($conversation)
                && $job->thread->is($thread)
                && $job->queue === 'emails'
                && $job->delay !== null;
        });
    }

    public function test_send_reply_to_customer_skips_imported_threads(): void
    {
        Queue::fake();

        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => 'customer@example.com',
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'imported' => true,
        ]);

        (new SendReplyToCustomer)->handle(new UserCreatedConversation($conversation, $thread));

        Queue::assertNothingPushed();
    }

    public function test_send_reply_to_customer_skips_phone_conversations_without_a_customer_email(): void
    {
        Queue::fake();

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Conversation::TYPE_PHONE,
            'customer_email' => null,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'imported' => false,
        ]);

        $customer = Customer::factory()->withoutEmail()->create();
        $conversation->setRelation('customer', $customer);

        (new SendReplyToCustomer)->handle(new UserCreatedConversation($conversation, $thread));

        Queue::assertNothingPushed();
    }

    public function test_send_auto_reply_queues_job_when_mailbox_and_sender_are_eligible(): void
    {
        Queue::fake();

        $mailbox = Mailbox::factory()->withAutoReply()->create();
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => 'customer@example.com',
            'subject' => 'Need help with billing',
            'status' => Conversation::STATUS_ACTIVE,
            'imported' => false,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'imported' => false,
            'headers' => null,
            'meta' => null,
        ]);

        (new SendAutoReply)->handle(new CustomerCreatedConversation($conversation, $thread, [
            'email' => 'customer@example.com',
            'name' => 'Customer Example',
        ]));

        Queue::assertPushed(SendAutoReplyJob::class, function (SendAutoReplyJob $job) use ($conversation, $mailbox, $thread): bool {
            return $job->conversation->is($conversation)
                && $job->thread->is($thread)
                && $job->mailbox->is($mailbox)
                && $job->senderInfo['email'] === 'customer@example.com'
                && $job->queue === 'emails';
        });
    }

    public function test_send_auto_reply_skips_internal_mailbox_addresses(): void
    {
        Queue::fake();

        $mailbox = Mailbox::factory()->withAutoReply()->create();
        Mailbox::factory()->create(['email' => 'internal@example.com']);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_email' => 'internal@example.com',
            'subject' => 'Internal loop',
            'status' => Conversation::STATUS_ACTIVE,
            'imported' => false,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'imported' => false,
            'headers' => null,
            'meta' => null,
        ]);

        (new SendAutoReply)->handle(new CustomerCreatedConversation($conversation, $thread, [
            'email' => 'internal@example.com',
            'name' => 'Internal Sender',
        ]));

        Queue::assertNothingPushed();
    }

    public function test_new_message_received_reopens_closed_conversations_and_notifies_relevant_users(): void
    {
        Queue::fake();

        $assignedUser = User::factory()->create();
        $watcher = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach([$assignedUser->id, $watcher->id]);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $assignedUser->id,
            'status' => Conversation::STATUS_CLOSED,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        (new HandleNewMessage)->handle(new NewMessageReceived($thread, $conversation));

        $conversation->refresh();

        $this->assertSame(Conversation::STATUS_ACTIVE, $conversation->status);

        Queue::assertPushed(SendNotificationToUsersJob::class, function (SendNotificationToUsersJob $job) use ($assignedUser, $conversation, $thread, $watcher): bool {
            /** @var array<int, int> $threadIds */
            $threadIds = $job->threads->pluck('id')->sort()->values()->all();

            return $job->conversation->is($conversation)
                && $this->queuedUserIds($job) === [$assignedUser->id, $watcher->id]
                && $threadIds === [$thread->id];
        });
    }

    public function test_send_password_changed_invokes_the_user_hook_when_present(): void
    {
        $user = new class
        {
            public bool $called = false;

            public function sendPasswordChanged(): void
            {
                $this->called = true;
            }
        };

        (new SendPasswordChanged)->handle(new PasswordReset($user));

        $this->assertTrue($user->called);
    }
}
