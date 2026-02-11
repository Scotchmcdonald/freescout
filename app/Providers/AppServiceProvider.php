<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use App\Services\Ui\WidgetRegistryService;
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
use App\Policies\MailboxPolicy;
use App\Policies\ThreadPolicy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Cache\RateLimiting\Limit;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WidgetRegistryService::class, function ($app) {
            return new WidgetRegistryService();
        });

        // Alias bindings for backward compatibility & test resolution
        $this->app->alias(WidgetRegistryService::class, 'App\Services\Ui\WidgetRegistry');

        // Register Data Import Setting Section
        \Eventy::addFilter('settings.sections', function($sections) {
            $sections['data_import'] = [
                'title' => __('Data Import'),
                'route' => 'settings.data_import',
                'icon' => 'upload',
                'order' => 800
            ];
            return $sections;
        });

        $this->app->singleton(\App\Services\EntitlementEngineService::class, function ($app) {
            return new \App\Services\EntitlementEngineService();
        });
        $this->app->alias(\App\Services\EntitlementEngineService::class, 'App\Services\EntitlementEngine');

        $this->app->singleton(\App\Services\UserDirectoryRegistryService::class, function ($app) {
             return new \App\Services\UserDirectoryRegistryService();
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
        // Add REGEXP support for SQLite (testing env)
        if (DB::connection() instanceof \Illuminate\Database\SQLiteConnection) {
            DB::connection()->getPdo()->sqliteCreateFunction('REGEXP', function ($pattern, $value) {
                mb_regex_encoding('UTF-8');
                return (false !== mb_ereg($pattern, $value)) ? 1 : 0;
            });
        }

        // Register navigation items
        $nav = $this->app->make(\App\Services\Navigation\NavigationService::class);
        if ($nav) {
            $nav->registerItem('Milestones', 'milestones.index', null, 'icon-milestone', 'Projects');
            $nav->registerItem('Knowledge Base', 'knowledgebase.index', null, 'icon-knowledge', 'Knowledge');
        }

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

        // Register rate limiters for webhooks
        $this->configureWebhookRateLimiters();
        
        // Client Portal policies for data isolation
        Gate::policy(\Modules\Crm\Models\Client::class, ClientPolicy::class);
        Gate::policy(\Modules\Crm\Models\ClientUser::class, ClientUserPolicy::class);

        // Monitor queue health
        Event::listen(JobProcessed::class, function (JobProcessed $event) {
            Cache::put('last_run_queue', now()->timestamp);
        });
    }

    /**
     * Configure rate limiters for webhook endpoints
     */
    protected function configureWebhookRateLimiters(): void
    {
        // Google webhooks: 60 requests per minute per IP
        RateLimiter::for('google_webhooks', function ($request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Action1 webhooks: 60 requests per minute per IP
        RateLimiter::for('action1_webhooks', function ($request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
