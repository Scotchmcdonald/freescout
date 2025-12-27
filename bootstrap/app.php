<?php

use App\Providers\EventServiceProvider;
use App\Providers\ModuleCompatibilityServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Qirolab\Theme\ThemeServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        EventServiceProvider::class,
        ModuleCompatibilityServiceProvider::class,
        ThemeServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust proxies - Configure for load balancer deployments
        // Uncomment and configure if deploying behind nginx, HAProxy, AWS ALB, etc.
        // $middleware->trustProxies(at: '*');
        // Or specify trusted proxy IPs:
        // $middleware->trustProxies(at: ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16']);
        
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'theme' => \App\Http\Middleware\ApplyUserTheme::class,
            'billing.auth' => \Modules\Billing\Http\Middleware\EnsureUserCanAccessCompanyBilling::class,
        ]);
        
        // Add middleware to web group:
        // - ResponseHeaders: Security headers (X-Content-Type-Options, etc.)
        // - FrameGuard: Security against clickjacking
        // - ApplyUserTheme: Apply user's selected theme
        // - Localize: Set user's preferred locale
        // - LogoutIfDeleted: Force logout deleted/disabled users
        // - CustomHandle: Chat mode toggle and module hooks
        $middleware->web(append: [
            \App\Http\Middleware\ResponseHeaders::class,
            \App\Http\Middleware\FrameGuard::class,
            \App\Http\Middleware\ApplyUserTheme::class,
            \App\Http\Middleware\Localize::class,
            \App\Http\Middleware\LogoutIfDeleted::class,
            \App\Http\Middleware\CustomHandle::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {})->create();
