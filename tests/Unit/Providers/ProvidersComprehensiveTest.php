<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Events\CustomerCreatedConversation;
use App\Events\NewMessageReceived;
use App\Policies\ClientPolicy;
use App\Policies\ClientUserPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\FolderPolicy;
use App\Policies\MailboxPolicy;
use App\Policies\ThreadPolicy;
use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\ModuleCompatibilityServiceProvider;
use App\Services\EntitlementEngine;
use App\Services\Navigation\NavigationService;
use App\Services\Ui\WidgetRegistryService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Modules\PIB\Services\EntitlementEngineService;
use Nwidart\Modules\Laravel\LaravelFileRepository;
use Nwidart\Modules\Module;
use Tests\UnitTestCase;

/**
 * Focused provider contract tests.
 *
 * These assertions target concrete container bindings, policies, listener
 * registrations, and compatibility macros instead of placeholder "does not
 * throw" coverage.
 */
class ProvidersComprehensiveTest extends UnitTestCase
{
    /**
     * @return mixed
     */
    private function readProtectedProperty(object $instance, string $property)
    {
        $reflection = new \ReflectionClass($instance);
        $propertyReflection = $reflection->getProperty($property);
        $propertyReflection->setAccessible(true);

        return $propertyReflection->getValue($instance);
    }

    public function test_app_service_provider_registers_core_singletons_and_aliases(): void
    {
        $provider = new AppServiceProvider($this->app);
        $provider->register();

        $widgetRegistry = $this->app->make(WidgetRegistryService::class);
        $this->assertInstanceOf(WidgetRegistryService::class, $widgetRegistry);
        $this->assertSame($widgetRegistry, $this->app->make(WidgetRegistryService::class));
        $this->assertSame($widgetRegistry, $this->app->make('App\Services\Ui\WidgetRegistry'));

        $entitlementEngine = $this->app->make(EntitlementEngine::class);
        $this->assertInstanceOf(EntitlementEngineService::class, $entitlementEngine);
        $this->assertSame($entitlementEngine, $this->app->make(EntitlementEngineService::class));
    }

    public function test_app_service_provider_boot_registers_navigation_policies_and_rate_limiters(): void
    {
        $provider = new AppServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        $navigation = $this->app->make(NavigationService::class);
        $items = collect($navigation->getItems());

        $this->assertTrue($items->contains(fn (array $item): bool => $item['label'] === 'Milestones' && $item['route'] === 'milestones.index'));
        $this->assertTrue($items->contains(fn (array $item): bool => $item['label'] === 'Knowledge Base' && $item['route'] === 'knowledgebase.index'));

        $this->assertInstanceOf(ConversationPolicy::class, Gate::getPolicyFor(\App\Models\Conversation::class));
        $this->assertInstanceOf(MailboxPolicy::class, Gate::getPolicyFor(\App\Models\Mailbox::class));
        $this->assertInstanceOf(ThreadPolicy::class, Gate::getPolicyFor(\App\Models\Thread::class));
        $this->assertInstanceOf(FolderPolicy::class, Gate::getPolicyFor(\App\Models\Folder::class));
        $this->assertInstanceOf(ClientPolicy::class, Gate::getPolicyFor(\Modules\Crm\Models\Client::class));
        $this->assertInstanceOf(ClientUserPolicy::class, Gate::getPolicyFor(\App\Models\User::class));

        $this->assertIsCallable(RateLimiter::limiter('google_webhooks'));
        $this->assertIsCallable(RateLimiter::limiter('action1_webhooks'));
        $this->assertIsCallable(RateLimiter::limiter('action1_script_callbacks'));
    }

    public function test_event_service_provider_registers_expected_listener_mappings(): void
    {
        $provider = new EventServiceProvider($this->app);
        $listen = $this->readProtectedProperty($provider, 'listen');

        $this->assertSame([\App\Listeners\LogRegisteredUser::class], $listen[Registered::class]);
        $this->assertSame([\App\Listeners\UpdateMailboxCounters::class], $listen[ConversationStatusChanged::class]);
        $this->assertSame([
            \App\Listeners\UpdateMailboxCounters::class,
            \App\Listeners\SendNotificationToUsers::class,
        ], $listen[ConversationUserChanged::class]);
        $this->assertSame([
            \App\Listeners\SendAutoReply::class,
            \App\Listeners\SendNotificationToUsers::class,
        ], $listen[CustomerCreatedConversation::class]);
        $this->assertSame([\App\Listeners\HandleNewMessage::class], $listen[NewMessageReceived::class]);
    }

    public function test_event_service_provider_registers_expected_model_observers(): void
    {
        $provider = new EventServiceProvider($this->app);
        $observers = $this->readProtectedProperty($provider, 'observers');

        $this->assertSame([\App\Observers\AttachmentObserver::class], $observers[\App\Models\Attachment::class]);
        $this->assertSame([\App\Observers\ConversationObserver::class], $observers[\App\Models\Conversation::class]);
        $this->assertSame([\App\Observers\UserObserver::class], $observers[\App\Models\User::class]);
    }

    public function test_event_service_provider_disables_automatic_discovery(): void
    {
        $provider = new EventServiceProvider($this->app);

        $this->assertFalse($provider->shouldDiscoverEvents());
    }

    public function test_module_compatibility_service_provider_registers_module_macros(): void
    {
        $provider = new ModuleCompatibilityServiceProvider($this->app);
        $provider->boot();

        $this->assertTrue(Module::hasMacro('getAlias'));
        $this->assertTrue(LaravelFileRepository::hasMacro('findByAlias'));
    }

    public function test_module_repository_can_find_modules_by_alias_or_lower_name(): void
    {
        $provider = new ModuleCompatibilityServiceProvider($this->app);
        $provider->boot();

        $matchingModule = new class
        {
            public function get(string $key): ?string
            {
                return $key === 'alias' ? 'billing' : null;
            }

            public function getLowerName(): string
            {
                return 'pib';
            }
        };

        $otherModule = new class
        {
            public function get(string $key): ?string
            {
                return $key === 'alias' ? 'support' : null;
            }

            public function getLowerName(): string
            {
                return 'helpdesk';
            }
        };

        $repository = new class([$matchingModule, $otherModule]) extends LaravelFileRepository
        {
            public function __construct(private array $modules) {}

            public function all(): array
            {
                return $this->modules;
            }
        };

        $this->assertSame($matchingModule, $repository->findByAlias('billing'));
        $this->assertSame($matchingModule, $repository->findByAlias('pib'));
        $this->assertNull($repository->findByAlias('missing'));
    }
}
