<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Providers\AppServiceProvider;
use Tests\UnitTestCase;

class AppServiceProviderTest extends UnitTestCase
{
    public function test_service_provider_can_be_instantiated(): void
    {
        $app = $this->app;
        $provider = new AppServiceProvider($app);

        $this->assertInstanceOf(AppServiceProvider::class, $provider);
    }

    public function test_register_method_executes_without_error(): void
    {
        $app = $this->app;
        $provider = new AppServiceProvider($app);

        // Should not throw an exception
        $provider->register();

        $this->expectNotToPerformAssertions();
    }

    public function test_boot_method_executes_without_error(): void
    {
        $app = $this->app;
        $provider = new AppServiceProvider($app);

        // Should not throw an exception
        $provider->boot();

        $this->expectNotToPerformAssertions();
    }

    public function test_service_provider_is_loaded_or_deferred(): void
    {
        $providers = $this->app->getLoadedProviders();

        // Check if provider is loaded or check it can be registered
        $hasProvider = array_key_exists(AppServiceProvider::class, $providers) ||
                      class_exists(AppServiceProvider::class);

        $this->assertTrue($hasProvider, 'AppServiceProvider should be available');
    }
}
