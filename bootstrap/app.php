<?php

use App\Providers\EventServiceProvider;
use App\Providers\ModuleCompatibilityServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Qirolab\Theme\ThemeServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\AppServiceProvider::class,
        \Nwidart\Modules\LaravelModulesServiceProvider::class,
        EventServiceProvider::class,
        ModuleCompatibilityServiceProvider::class,
        ThemeServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust proxies — required for OrbStack/Docker/Cloudflare Tunnel deployments.
        // Set TRUSTED_PROXIES=* in .env to trust all proxies (safe behind a Cloudflare Tunnel).
        // Restrict to specific IPs in production: TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));
        
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'theme' => \App\Http\Middleware\ApplyUserTheme::class,
            'scope.company' => \App\Http\Middleware\ScopeCompany::class,
            'impersonate.protect' => \App\Http\Middleware\PreventImpersonatorWrites::class,
            'webhook.verify' => \App\Http\Middleware\VerifyWebhookSignature::class,
        ]);
        
        // Add middleware to web group:
        // - AddSentryContext: Enrich error reports with request/user context
        // - ResponseHeaders: Security headers (X-Content-Type-Options, etc.)
        // - FrameGuard: Security against clickjacking
        // - ApplyUserTheme: Apply user's selected theme
        // - Localize: Set user's preferred locale
        // - LogoutIfDeleted: Force logout deleted/disabled users
        // - CustomHandle: Chat mode toggle and module hooks
        $middleware->web(append: [
            \App\Http\Middleware\AddSentryContext::class,
            \App\Http\Middleware\ResponseHeaders::class,
            \App\Http\Middleware\FrameGuard::class,
            \App\Http\Middleware\ApplyUserTheme::class,
            \App\Http\Middleware\Localize::class,
            \App\Http\Middleware\LogoutIfDeleted::class,
            \App\Http\Middleware\CustomHandle::class,
            \App\Http\Middleware\PreventImpersonatorWrites::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {})->create();
