<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\CustomerCreatedConversation;
use App\Events\CustomerReplied;
use App\Events\NewMessageReceived;
use App\Listeners\HandleNewMessage;
use App\Listeners\SendAutoReply;
use App\Models\Thread;
use App\Observers\ThreadObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        CustomerCreatedConversation::class => [
            SendAutoReply::class,
        ],
        CustomerReplied::class => [
            // Add listeners for customer replies here
        ],
        NewMessageReceived::class => [
            HandleNewMessage::class,
        ],
    ];

    /**
     * The model observers for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $observers = [
        Thread::class => [ThreadObserver::class],
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