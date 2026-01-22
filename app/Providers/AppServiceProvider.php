<?php

namespace App\Providers;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use App\Services\Ui\WidgetRegistry;
use App\Observers\AttachmentObserver;
use App\Observers\ConversationObserver;
use App\Observers\CustomerObserver;
use App\Observers\MailboxObserver;
use App\Observers\ThreadObserver;
use App\Observers\UserObserver;
use App\Policies\ClientPolicy;
use App\Policies\ClientUserPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\FolderPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\MailboxPolicy;
use App\Policies\ThreadPolicy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WidgetRegistry::class, function ($app) {
            return new WidgetRegistry();
        });

        $this->app->singleton(\App\Services\UserDirectoryRegistry::class, function ($app) {
             return new \App\Services\UserDirectoryRegistry();
        });

        $this->app->singleton(\App\Services\Navigation\NavigationService::class, function ($app) {
            return new \App\Services\Navigation\NavigationService();
        });

        // Register class aliases for backward compatibility
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Helper', \App\Misc\Helper::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register navigation items
        $nav = $this->app->make(\App\Services\Navigation\NavigationService::class);
        if ($nav) {
            $nav->registerItem('Milestones', 'milestones.index', null, 'icon-milestone'); // Assuming a route name and icon
        }

        // Register model observers
        Conversation::observe(ConversationObserver::class);
        User::observe(UserObserver::class);
        Customer::observe(CustomerObserver::class);
        Mailbox::observe(MailboxObserver::class);
        Attachment::observe(AttachmentObserver::class);
        Thread::observe(ThreadObserver::class);

        // Register authorization policies
        Gate::before(function ($user, $ability) {
            if ($user instanceof \App\Models\User) {
                // Only log and check admin for internal users
                // Log::info("Gate Check: $ability for user " . $user->id . " Role: " . $user->role);
                return $user->isAdmin() ? true : null;
            }
            return null;
        });

        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(Mailbox::class, MailboxPolicy::class);
        Gate::policy(Thread::class, ThreadPolicy::class);
        Gate::policy(Folder::class, FolderPolicy::class);
        
        // Client Portal policies for data isolation
        Gate::policy(\Modules\Crm\Models\Client::class, ClientPolicy::class);
        Gate::policy(\Modules\Crm\Models\ClientUser::class, ClientUserPolicy::class);
        Gate::policy(\Modules\PIB\Models\Invoice::class, InvoicePolicy::class);

        // Monitor queue health
        Event::listen(JobProcessed::class, function (JobProcessed $event) {
            Cache::put('last_run_queue', now()->timestamp);
        });
    }
}
