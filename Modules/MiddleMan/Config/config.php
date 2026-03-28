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

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker
    |--------------------------------------------------------------------------
    |
    | Guarantees zero production impact. If MiddleMan encounters repeated
    | failures (cache/queue down) or an event storm, it auto-disables
    | and recovers after a cooldown period.
    |
    */
    'circuit_breaker' => [
        // Number of consecutive failures before tripping the breaker
        'failure_threshold'        => (int) env('MIDDLEMAN_CB_FAILURE_THRESHOLD', 5),

        // Max events per second before storm detection trips the breaker (memory protection)
        'storm_threshold_per_second' => (int) env('MIDDLEMAN_CB_STORM_THRESHOLD', 500),

        // Max queued jobs before backpressure trips the breaker
        'queue_depth_limit'        => (int) env('MIDDLEMAN_CB_QUEUE_DEPTH', 10000),

        // Seconds to wait before attempting half-open recovery
        'cooldown_seconds'         => (int) env('MIDDLEMAN_CB_COOLDOWN', 60),

        // Seconds between cache syncs (local state is used between syncs for speed)
        'sync_interval_seconds'    => (int) env('MIDDLEMAN_CB_SYNC_INTERVAL', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Discovery Cache TTL (seconds)
    |--------------------------------------------------------------------------
    |
    | How long the EventDiscoveryService caches the discovered event map.
    | Set to 0 to disable caching (not recommended in production).
    |
    */
    'discovery_cache_ttl' => (int) env('MIDDLEMAN_DISCOVERY_CACHE_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Marshal Presets
    |--------------------------------------------------------------------------
    |
    | Maximum number of saved presets per event class for the Marshal UI.
    |
    */
    'max_presets_per_event' => (int) env('MIDDLEMAN_MAX_PRESETS', 25),

    /*
    |--------------------------------------------------------------------------
    | Kroki Diagram Rendering
    |--------------------------------------------------------------------------
    |
    | Enables server-side topology graph rendering via a Kroki instance.
    | For standalone compose deployments, point base_url to the host-accessible
    | endpoint, e.g. http://host.docker.internal:8001 in local Docker setups.
    |
    */
    'kroki' => [
        'enabled'         => (bool) env('MIDDLEMAN_KROKI_ENABLED', false),
        'base_url'        => env('MIDDLEMAN_KROKI_URL', 'http://kroki:8000'),
        'timeout_seconds' => (int) env('MIDDLEMAN_KROKI_TIMEOUT', 10),
    ],
];
