<?php

namespace App\Providers;

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
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Conversation::observe(ConversationObserver::class);
        User::observe(UserObserver::class);
        Customer::observe(CustomerObserver::class);
        Mailbox::observe(MailboxObserver::class);
        Attachment::observe(AttachmentObserver::class);
        Thread::observe(ThreadObserver::class);
    }
}
