<?php

namespace App\Providers;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Thread;
use App\Policies\ConversationPolicy;
use App\Policies\FolderPolicy;
use App\Policies\ThreadPolicy;
use Illuminate\Support\Facades\Gate;
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
        // Register authorization policies
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(Thread::class, ThreadPolicy::class);
        Gate::policy(Folder::class, FolderPolicy::class);
    }
}
