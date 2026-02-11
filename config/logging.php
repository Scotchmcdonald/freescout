<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => (string) env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => (string) env('LOG_LEVEL', 'debug'),
            'days' => (int) env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        // Business Events Channel - For audit trail and business logic events
        'business' => [
            'driver' => 'daily',
            'path' => storage_path('logs/business.log'),
            'level' => 'info',
            'days' => 90, // Longer retention for audit purposes
            'replace_placeholders' => true,
        ],

        // Performance Metrics Channel - For slow queries, API calls, etc.
        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => 'warning',
            'days' => 7,
            'replace_placeholders' => true,
        ],

        // Security Events Channel - For authentication, authorization, etc.
        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => 'warning',
            'days' => 90, // Longer retention for compliance
            'replace_placeholders' => true,
        ],

        // Audit Trail Channel - For sensitive operations (credit adjustments, approvals, etc.)
        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit.log'),
            'level' => 'info',
            'days' => 365, // 1 year retention for compliance
            'replace_placeholders' => true,
        ],

        // Queue Processing Channel - For job failures and retries
        'queue' => [
            'driver' => 'daily',
            'path' => storage_path('logs/queue.log'),
            'level' => 'info',
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => (string) env('LOG_SLACK_WEBHOOK_URL', ''),
            'username' => (string) env('LOG_SLACK_USERNAME', 'Laravel Log'),
            'emoji' => (string) env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => (string) env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => (string) env('LOG_LEVEL', 'debug'),
            'handler' => (string) env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => (string) env('PAPERTRAIL_URL', ''),
                'port' => (string) env('PAPERTRAIL_PORT', ''),
                'connectionString' => 'tls://'.(string) env('PAPERTRAIL_URL', '').':'.(string) env('PAPERTRAIL_PORT', ''),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => (string) env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => (string) env('LOG_STDERR_FORMATTER', ''),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => (string) env('LOG_LEVEL', 'debug'),
            'facility' => (int) env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => (string) env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];
