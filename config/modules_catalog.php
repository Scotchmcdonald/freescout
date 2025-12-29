<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Repository Catalog
    |--------------------------------------------------------------------------
    |
    | List of known module repositories that can be installed.
    | These will appear in the dropdown on the module installation page.
    |
    */

    'repositories' => [
        [
            'name' => 'Email Migration',
            'url' => 'https://github.com/BorealTek/Email-Migration',
            'description' => 'IMAP email migration and mailbox seeding tools',
        ],
        [
            'name' => 'CRM Module',
            'url' => 'https://github.com/BorealTek/CRM-Module',
            'description' => 'Customer Relationship Management features',
        ],
        [
            'name' => 'Billing',
            'url' => 'https://github.com/Scotchmcdonald/Billing',
            'description' => 'Billing Module',
        ],
        [
            'name' => 'Inventory',
            'url' => 'https://github.com/Scotchmcdonald/Inventory',
            'description' => 'MSP Inventory and PriceBook Management',
        ],
        [
            'name' => 'DevFeedback',
            'url' => 'https://github.com/Scotchmcdonald/DevFeedback',
            'description' => 'Developer Feedback Module',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Remote Catalog URL
    |--------------------------------------------------------------------------
    |
    | Optional: URL to fetch additional repositories from a remote JSON source.
    | Set to null to disable remote catalog fetching.
    |
    */

    'remote_catalog_url' => null,
    // Example: 'https://raw.githubusercontent.com/BorealTek/freescout-modules/main/catalog.json'
];
