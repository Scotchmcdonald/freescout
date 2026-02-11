<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    'admin_email' => env('ADMIN_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'host_src_path' => env('HOST_SRC_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | FreeScout Brand Colors
    |--------------------------------------------------------------------------
    |
    | These colors are used throughout the application and in email templates.
    |
    */

    'colors' => [
        'main_light'    => '#0078d7',
        'main_dark'     => '#005a9e',
        'note'          => '#ffc646',
        'text_note'     => '#e6b216',
        'text_customer' => '#8d959b',
        'text_user'     => '#8d959b',
        'bg_user_reply' => '#f4f8fd',
        'bg_note'       => '#fffbf1',
    ],

    /*
    |--------------------------------------------------------------------------
    | FreeScout URL
    |--------------------------------------------------------------------------
    |
    | FreeScout project website URL for branding in emails.
    |
    */

    'freescout_url' => 'https://freescout.net',

    /*
    |--------------------------------------------------------------------------
    | FreeScout API
    |--------------------------------------------------------------------------
    |
    | API endpoints for module marketplace and updates.
    |
    */

    'freescout_api' => 'https://freescout.net/wp-json/',
    'freescout_alt_api' => 'https://freescout.net/wp-json/',
    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Follow-Up Reminder Settings
    |--------------------------------------------------------------------------
    |
    | Default number of days until a follow-up reminder is sent if no specific
    | follow-up date is set when replying to a conversation.
    |
    */

    'default_follow_up_days' => env('DEFAULT_FOLLOW_UP_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | Seeder Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for database seeders.
    |
    */

    'seeding' => [
        'admin' => [
            'password' => env('ADMIN_PASSWORD', 'admin123456789'),
            'first_name' => env('ADMIN_FIRST_NAME', 'System'),
            'last_name' => env('ADMIN_LAST_NAME', 'Administrator'),
        ],
        'agent' => [
            'email' => env('AGENT_EMAIL', 'agent@example.com'),
            'password' => env('AGENT_PASSWORD', 'agent123456789'),
            'first_name' => env('AGENT_FIRST_NAME', 'Support'),
            'last_name' => env('AGENT_LAST_NAME', 'Agent'),
        ],
        'finance' => [
            'email' => env('FINANCE_EMAIL', 'finance@example.com'),
            'password' => env('FINANCE_PASSWORD', 'finance123456789'),
            'first_name' => env('FINANCE_FIRST_NAME', 'Finance'),
            'last_name' => env('FINANCE_LAST_NAME', 'Manager'),
        ],
        'reporter' => [
            'email' => env('REPORTER_EMAIL', 'reporter@example.com'),
            'password' => env('REPORTER_PASSWORD', 'reporter123456789'),
            'first_name' => env('REPORTER_FIRST_NAME', 'Report'),
            'last_name' => env('REPORTER_LAST_NAME', 'Viewer'),
        ]
    ],

];
