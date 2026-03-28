<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | MiddleMan Module — Master Switch
    |--------------------------------------------------------------------------
    |
    | When false the entire module is dormant: no listeners are registered,
    | the custom dispatcher is NOT bound, and the ServiceProvider exits in
    | microseconds. Toggle via MIDDLEMAN_ENABLED env var.
    |
    */
    'enabled' => (bool) env('MIDDLEMAN_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Rule Cache Store
    |--------------------------------------------------------------------------
    |
    | The cache store used for the in-memory rule engine.  Redis is strongly
    | recommended for production.  The "array" driver may be used in tests.
    |
    */
    'cache_store' => env('MIDDLEMAN_CACHE_STORE', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connection & Queue Name
    |--------------------------------------------------------------------------
    |
    | Background jobs (log writes, intercept writes) are dispatched to this
    | connection/queue so they never block the HTTP request cycle.
    |
    */
    'queue_connection' => env('MIDDLEMAN_QUEUE_CONNECTION', 'redis'),
    'queue_name'       => env('MIDDLEMAN_QUEUE', 'middleman'),

    /*
    |--------------------------------------------------------------------------
    | Log Retention (days)
    |--------------------------------------------------------------------------
    |
    | Logs older than this value are eligible for pruning by the scheduled
    | cleanup command.
    |
    */
    'log_retention_days' => (int) env('MIDDLEMAN_LOG_RETENTION_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Maximum Payload Size (bytes)
    |--------------------------------------------------------------------------
    |
    | Events whose serialized payload exceeds this limit will be truncated
    | to prevent database bloat.  Default: 64 KB.
    |
    */
    'max_payload_bytes' => (int) env('MIDDLEMAN_MAX_PAYLOAD_BYTES', 65536),

    /*
    |--------------------------------------------------------------------------
    | Event Scan Paths
    |--------------------------------------------------------------------------
    |
    | Directories scanned by the EventScanner for the Marshalling UI.
    | Relative to base_path().
    |
    */
    'scan_paths' => [
        'app/Events',
        'Modules/*/Events',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Keys
    |--------------------------------------------------------------------------
    |
    | Internal cache key prefixes — you should not need to change these.
    |
    */
    'cache_keys' => [
        'rules'            => 'middleman:rules',
        'logging_active'   => 'middleman:logging_active',
        'intercept_active' => 'middleman:intercept_active',
        'muted_listeners'  => 'middleman:muted_listeners',
    ],
];
