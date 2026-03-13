<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Events\CustomerCreatedConversation;
use App\Events\CustomerReplied;
use App\Events\NewMessageReceived;
use App\Events\UserAddedNote;
use App\Events\UserCreatedConversation;
use App\Events\UserDeleted;
use App\Events\UserReplied;
use App\Listeners\HandleNewMessage;
use App\Listeners\LogFailedLogin;
use App\Listeners\LogLockout;
use App\Listeners\LogPasswordReset;
use App\Listeners\LogRegisteredUser;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use App\Listeners\LogUserDeletion;
use App\Listeners\RememberUserLocale;
use App\Listeners\SendAutoReply;
use App\Listeners\SendNotificationToUsers;
use App\Listeners\SendPasswordChanged;
use App\Listeners\SendReplyToCustomer;
use App\Listeners\UpdateMailboxCounters;
use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use App\Observers\AttachmentObserver;
use App\Observers\ConversationObserver;
use App\Observers\CustomerObserver;
use App\Observers\MailboxObserver;
use App\Observers\ThreadObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Authentication events
        Registered::class => [
            LogRegisteredUser::class,
        ],
        Login::class => [
            RememberUserLocale::class,
            LogSuccessfulLogin::class,
        ],
        Failed::class => [
            LogFailedLogin::class,
        ],
        Logout::class => [
            LogSuccessfulLogout::class,
        ],
        Lockout::class => [
            LogLockout::class,
        ],
        PasswordReset::class => [
            LogPasswordReset::class,
            SendPasswordChanged::class,
        ],

        // User events
        UserDeleted::class => [
            LogUserDeletion::class,
        ],

        // Conversation events
        ConversationStatusChanged::class => [
            UpdateMailboxCounters::class,
        ],
        ConversationUserChanged::class => [
            UpdateMailboxCounters::class,
            SendNotificationToUsers::class,
        ],
        UserReplied::class => [
            SendReplyToCustomer::class,
            SendNotificationToUsers::class,
        ],
        CustomerReplied::class => [
            SendNotificationToUsers::class,
        ],
        UserCreatedConversation::class => [
            SendReplyToCustomer::class,
            SendNotificationToUsers::class,
        ],
        CustomerCreatedConversation::class => [
            SendAutoReply::class,
            SendNotificationToUsers::class,
        ],
        UserAddedNote::class => [
            SendNotificationToUsers::class,
        ],

        // Message handling
        NewMessageReceived::class => [
            HandleNewMessage::class,
        ],
    ];

    /**
     * The model observers for the application.
     *
     * @var array<class-string, class-string|array<int, class-string>>
     */
    protected $observers = [
        Attachment::class => [AttachmentObserver::class],
        Conversation::class => [ConversationObserver::class],
        Customer::class => [CustomerObserver::class],
        Mailbox::class => [MailboxObserver::class],
        Thread::class => [ThreadObserver::class],
        User::class => [UserObserver::class],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void {}

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
