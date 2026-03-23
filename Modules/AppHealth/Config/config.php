<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('APPHEALTH_ENABLED', true),
    'metrics_enabled' => (bool) env('APPHEALTH_METRICS_ENABLED', true),
    'trigger_evaluation_enabled' => (bool) env('APPHEALTH_TRIGGER_EVALUATION_ENABLED', true),
    'http_instrumentation_enabled' => (bool) env('APPHEALTH_HTTP_INSTRUMENTATION_ENABLED', true),
    'operator_ui_enabled' => (bool) env('APPHEALTH_OPERATOR_UI_ENABLED', true),

    'security' => [
        'internal_token' => (string) env('APPHEALTH_INTERNAL_TOKEN', ''),
        'header' => (string) env('APPHEALTH_TOKEN_HEADER', 'X-AppHealth-Token'),
        'allow_without_token_in_testing' => (bool) env('APPHEALTH_ALLOW_WITHOUT_TOKEN_IN_TESTING', false),
    ],

    'scheduler' => [
        'enabled' => (bool) env('APPHEALTH_SCHEDULER_ENABLED', true),
        'cron' => (string) env('APPHEALTH_EVALUATION_CRON', '*/15 * * * *'),
    ],

    'thresholds' => [
        'api_p95_seconds' => (float) env('APPHEALTH_STAGE_A_API_P95_SECONDS', 2.0),
        'queue_wait_p95_seconds' => (float) env('APPHEALTH_STAGE_A_QUEUE_WAIT_P95_SECONDS', 30.0),
        'failed_job_ratio' => (float) env('APPHEALTH_STAGE_A_FAILED_JOB_RATIO', 0.001),
        'worker_cpu_breach_minutes_24h' => (int) env('APPHEALTH_STAGE_A_WORKER_CPU_BREACH_MINUTES_24H', 240),
        'db_cpu_breach_minutes_24h' => (int) env('APPHEALTH_STAGE_A_DB_CPU_BREACH_MINUTES_24H', 240),
    ],

    'inputs' => [
        'api_p95_seconds' => env('APPHEALTH_INPUT_API_P95_SECONDS'),
        'queue_wait_p95_seconds' => env('APPHEALTH_INPUT_QUEUE_WAIT_P95_SECONDS'),
        'failed_job_ratio' => env('APPHEALTH_INPUT_FAILED_JOB_RATIO'),
        'worker_cpu_breach_minutes_24h' => env('APPHEALTH_INPUT_WORKER_CPU_BREACH_MINUTES_24H'),
        'db_cpu_breach_minutes_24h' => env('APPHEALTH_INPUT_DB_CPU_BREACH_MINUTES_24H'),
    ],

    'queue_names' => ['default', 'billing', 'sync'],

    'histogram_buckets' => [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0],

    'ingestion' => [
        'enabled' => (bool) env('APPHEALTH_INGESTION_ENABLED', true),
    ],

    'playbook' => [
        'consecutive_breach_weeks_required' => (int) env('APPHEALTH_CONSECUTIVE_BREACH_WEEKS', 2),
    ],

    'observability' => [
        'grafana_url' => env('APPHEALTH_GRAFANA_URL'),
        'prometheus_url' => env('APPHEALTH_PROMETHEUS_URL'),
    ],
];
