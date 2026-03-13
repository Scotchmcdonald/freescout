<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sentry DSN (Data Source Name)
    |--------------------------------------------------------------------------
    |
    | The DSN tells the SDK where to send events. You can find your DSN in
    | your Sentry project settings. If not provided, Sentry will be disabled.
    |
    */

    'dsn' => env('SENTRY_LARAVEL_DSN'),

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs
    |--------------------------------------------------------------------------
    |
    | Breadcrumbs are a trail of events that happened prior to an error.
    | They help provide context when debugging issues.
    |
    */

    'breadcrumbs' => [
        // Capture SQL queries as breadcrumbs
        'sql_queries' => filter_var(
            env('SENTRY_BREADCRUMBS_SQL_QUERIES_ENABLED', 'true'),
            FILTER_VALIDATE_BOOL
        ) || env('APP_ENV') === 'testing',

        // Capture SQL bindings in breadcrumbs (may contain sensitive data)
        'sql_bindings' => env('SENTRY_BREADCRUMBS_SQL_BINDINGS_ENABLED', false),

        // Capture Laravel logs as breadcrumbs
        'logs' => env('SENTRY_BREADCRUMBS_LOGS_ENABLED', true),

        // Capture cache operations as breadcrumbs
        'cache' => env('SENTRY_BREADCRUMBS_CACHE_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring (Transactions)
    |--------------------------------------------------------------------------
    |
    | Sample rate for performance monitoring. Set to 1.0 to trace 100% of
    | transactions, or 0.1 to trace 10%. Lower sample rates reduce overhead.
    |
    */

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Automatically uses APP_ENV but can be overridden if needed.
    |
    */

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),

    /*
    |--------------------------------------------------------------------------
    | Release Tracking
    |--------------------------------------------------------------------------
    |
    | Track application version for release-based error tracking.
    | Uses Git commit hash by default if available.
    |
    */

    'release' => env('SENTRY_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | Error Types to Report
    |--------------------------------------------------------------------------
    |
    | Which error types should be reported to Sentry.
    |
    */

    'error_types' => E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED,

    /*
    |--------------------------------------------------------------------------
    | Send Default PII (Personally Identifiable Information)
    |--------------------------------------------------------------------------
    |
    | When enabled, Sentry will send user IP addresses and other PII.
    | Disable for GDPR compliance or privacy concerns.
    |
    */

    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    /*
    |--------------------------------------------------------------------------
    | Ignored Exceptions
    |--------------------------------------------------------------------------
    |
    | List exception classes that should not be reported to Sentry.
    |
    */

    'ignore_exceptions' => [
        Illuminate\Auth\AuthenticationException::class,
        Illuminate\Validation\ValidationException::class,
        Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Before Send Callback
    |--------------------------------------------------------------------------
    |
    | Callback to modify or drop events before sending to Sentry.
    | Return null to prevent the event from being sent.
    |
    */

    // Array callables [ClassName, 'method'] are serializable (two plain strings)
    // and satisfy is_callable() for static methods — compatible with both
    // config:cache (var_export) and Sentry's OptionsResolver validation.
    'before_send' => [\App\Services\SentryBeforeSend::class, 'handle'],

    /*
    |--------------------------------------------------------------------------
    | Integrations
    |--------------------------------------------------------------------------
    |
    | Configure Sentry integrations. Set to false to disable specific ones.
    |
    */

    'integrations' => [
        // Integrations are automatically loaded by sentry-laravel
        // Custom integrations can be added here if needed in the future
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Tags
    |--------------------------------------------------------------------------
    |
    | Add custom tags to all Sentry events for better filtering.
    |
    */

    'tags' => [
        'server' => env('SERVER_NAME', gethostname()),
    ],

    /*
    |--------------------------------------------------------------------------
    | Before Breadcrumb Callback
    |--------------------------------------------------------------------------
    |
    | Callback to modify or drop breadcrumbs before adding them to the event.
    |
    */

    'before_breadcrumb' => [\App\Services\SentryBeforeBreadcrumb::class, 'handle'],

];
