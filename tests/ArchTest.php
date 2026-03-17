<?php

declare(strict_types=1);

// 1. debugging and loose code
arch('globals')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'die'])
    ->not->toBeUsed();

arch('architecture tests do not hit the database')
    ->expect('Tests\\Architecture')
    ->not->toUse('Illuminate\\Foundation\\Testing\\RefreshDatabase');

// 2. Core Blindness: App (FreeScout Core) should not depend on Feature Modules
// The App namespace contains the core application. It should be agnostic of specific feature modules.
arch('app core blindness')
    ->expect('App')
    ->not->toUse([
        'Modules\Action1',
        'Modules\AssetManagement',
        'Modules\ClientPortal',
        'Modules\ContractManager',
        'Modules\DevFeedback',
        'Modules\EmailMigration',
        'Modules\GoogleAdmin',
        'Modules\PIB',
        'Modules\Payment',
        'Modules\SoftwareSubscriptions',
    ]);

// 3. Core Blindness: CRM Module should not depend on Feature Modules
// CRM is a Core Module. It should not know about billing (PIB), assets, etc.
arch('crm core blindness')
    ->expect('Modules\Crm')
    ->not->toUse([
        'Modules\Action1',
        'Modules\AssetManagement',
        'Modules\ClientPortal',
        'Modules\ContractManager',
        'Modules\DevFeedback',
        'Modules\EmailMigration',
        'Modules\GoogleAdmin',
        'Modules\PIB',
        'Modules\Payment',
        'Modules\SoftwareSubscriptions',
    ]);

// 4. Layered Architecture & Naming Conventions
arch('naming conventions')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller')
    ->and('App\Jobs')
    ->toHaveSuffix('Job')
    ->and('App\Services')
    ->toHaveSuffix('Service');

// 5. Contracts should be Interfaces
arch('contracts')
    ->expect('App\Contracts')
    ->toBeInterfaces();

// 6. Queue Isolation
// High volume tasks should be queued (heuristic).
arch('jobs should queue')
    ->expect('App\Jobs')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

// 7. Service Isolation
// Services should typically not depend on Controllers
arch('services should not depend on controllers')
    ->expect('App\Services')
    ->not->toUse('App\Http\Controllers');

// 8. Security
// md5/sha1 must not be used for cryptographic purposes.
// Ignored classes use md5 only for non-crypto: Gravatar hashes, email message-ID generation, dedup.
arch('security')
    ->expect('App')
    ->not->toUse(['md5', 'sha1'])
    ->ignoring([
        'App\Misc\Helper',
        'App\Misc\MailHelper',
        'App\Models\User',
        'App\Jobs\SendNotificationToUsersJob',
    ]);

// 9. Strict Types
arch('strict types')
    ->expect('App')
    ->toUseStrictTypes();

// 10. Enums
arch('enums')
    ->expect('App\Enums')
    ->toBeEnums();

// 11. Value Objects
arch('value objects')
    ->expect('App\ValueObjects')
    ->toBeClasses()
    ->toUseStrictTypes();

// 12. DTOs
arch('dtos')
    ->expect('App\DataTransferObjects')
    // We expect DTOs to be simple. We can enforce readonly in PHP 8.2+
    // But let's check classes first.
    ->toBeClasses()
    ->toUseStrictTypes();

// 13. DTOs should be readonly (immutable data carriers)
arch('app dtos are readonly')
    ->expect('App\DataTransferObjects')
    ->toBeReadonly();

// 14. Module DTOs should also be readonly
arch('module dtos are readonly')
    ->expect([
        'Modules\Action1\DataTransferObjects',
        'Modules\Alerts\DataTransferObjects',
        'Modules\ContractManager\DataTransferObjects',
        'Modules\Crm\DataTransferObjects',
        'Modules\GoogleAdmin\DataTransferObjects',
        'Modules\Payment\DataTransferObjects',
        'Modules\SoftwareSubscriptions\DataTransferObjects',
    ])
    ->toBeReadonly();

// ──────────────────────────────────────────────────────────
// Infrastructure Module Blindness
// Infrastructure modules (Alerts, KnowledgeBase, WidgetRegistry)
// are shared utilities — they must not depend on feature modules.
// ──────────────────────────────────────────────────────────

// 15. Alerts must not depend on feature modules
// Note: Alerts listeners intentionally subscribe to events from feature modules
// (e.g. SoftwareDeploymentFailed) — those specific listeners are excluded.
arch('alerts core blindness')
    ->expect('Modules\Alerts')
    ->not->toUse([
        'Modules\Action1',
        'Modules\AssetManagement',
        'Modules\ClientPortal',
        'Modules\ContractManager',
        'Modules\DevFeedback',
        'Modules\EmailMigration',
        'Modules\GoogleAdmin',
        'Modules\PIB',
        'Modules\Payment',
        'Modules\SoftwareSubscriptions',
    ])
    ->ignoring('Modules\Alerts\Listeners')
    ->ignoring('Modules\Alerts\Providers\EventServiceProvider');

// 16. WidgetRegistry must not depend on any other module
arch('widget registry core blindness')
    ->expect('Modules\WidgetRegistry')
    ->not->toUse([
        'Modules\Action1',
        'Modules\AssetManagement',
        'Modules\ClientPortal',
        'Modules\ContractManager',
        'Modules\Crm',
        'Modules\DevFeedback',
        'Modules\EmailMigration',
        'Modules\GoogleAdmin',
        'Modules\PIB',
        'Modules\Payment',
        'Modules\SoftwareSubscriptions',
        'Modules\Alerts',
        'Modules\KnowledgeBase',
    ]);

// 17. KnowledgeBase must not depend on feature modules
arch('knowledge base core blindness')
    ->expect('Modules\KnowledgeBase')
    ->not->toUse([
        'Modules\Action1',
        'Modules\AssetManagement',
        'Modules\ClientPortal',
        'Modules\ContractManager',
        'Modules\DevFeedback',
        'Modules\EmailMigration',
        'Modules\GoogleAdmin',
        'Modules\PIB',
        'Modules\Payment',
        'Modules\SoftwareSubscriptions',
    ]);

// ──────────────────────────────────────────────────────────
// Feature Module Isolation
// Feature modules must not depend on other feature modules.
// They may only depend on App, Modules\Crm, and infrastructure modules.
// ──────────────────────────────────────────────────────────

// 18. DevFeedback is standalone — zero module dependencies
arch('devfeedback isolation')
    ->expect('Modules\DevFeedback')
    ->not->toUse([
        'Modules\Action1',
        'Modules\AssetManagement',
        'Modules\ClientPortal',
        'Modules\ContractManager',
        'Modules\Crm',
        'Modules\EmailMigration',
        'Modules\GoogleAdmin',
        'Modules\PIB',
        'Modules\Payment',
        'Modules\SoftwareSubscriptions',
        'Modules\Alerts',
        'Modules\WidgetRegistry',
        // 'Modules\KnowledgeBase', // Use allowed for seeding
    ]);

// 19. EmailMigration is standalone — zero module dependencies
arch('emailmigration isolation')
    ->expect('Modules\EmailMigration')
    ->not->toUse([
        'Modules\Action1',
        'Modules\AssetManagement',
        'Modules\ClientPortal',
        'Modules\ContractManager',
        'Modules\Crm',
        'Modules\DevFeedback',
        'Modules\GoogleAdmin',
        'Modules\PIB',
        'Modules\Payment',
        'Modules\SoftwareSubscriptions',
        'Modules\Alerts',
        'Modules\WidgetRegistry',
        // 'Modules\KnowledgeBase', // Use allowed for seeding
    ]);

// 20. GoogleAdmin only depends on CRM (integration module, exports events)
arch('googleadmin isolation')
    ->expect('Modules\GoogleAdmin')
    ->not->toUse([
        'Modules\Action1',
        'Modules\AssetManagement',
        'Modules\ClientPortal',
        'Modules\ContractManager',
        'Modules\DevFeedback',
        'Modules\EmailMigration',
        'Modules\PIB',
        'Modules\Payment',
        'Modules\SoftwareSubscriptions',
    ]);

// 21. Action1 only depends on CRM (integration module, exports events)
arch('action1 isolation')
    ->expect('Modules\Action1')
    ->not->toUse([
        'Modules\AssetManagement',
        'Modules\ClientPortal',
        'Modules\ContractManager',
        'Modules\DevFeedback',
        'Modules\EmailMigration',
        'Modules\GoogleAdmin',
        'Modules\PIB',
        'Modules\Payment',
        'Modules\SoftwareSubscriptions',
    ]);

// ──────────────────────────────────────────────────────────
// Extended Naming Conventions
// ──────────────────────────────────────────────────────────

// 22. Observers must have Observer suffix
arch('observer naming')
    ->expect('App\Observers')
    ->toHaveSuffix('Observer');

// 23. Policies must have Policy suffix
arch('policy naming')
    ->expect('App\Policies')
    ->toHaveSuffix('Policy');

// 24. Actions must have Action suffix
arch('action naming')
    ->expect('App\Actions')
    ->toHaveSuffix('Action');

// ──────────────────────────────────────────────────────────
// Module-Level Service & Job Guards
// ──────────────────────────────────────────────────────────

// 25. Module jobs should implement ShouldQueue
arch('module jobs should queue')
    ->expect([
        'Modules\Action1\Jobs',
        'Modules\Crm\Jobs',
        'Modules\EmailMigration\Jobs',
        'Modules\GoogleAdmin\Jobs',
        'Modules\PIB\Jobs',
        'Modules\Payment\Jobs',
    ])
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

// 26. Module services should not depend on controllers
arch('module services should not depend on controllers')
    ->expect([
        'Modules\PIB\Services',
        'Modules\Crm\Services',
        'Modules\EmailMigration\Services',
        'Modules\Payment\Services',
        'Modules\ContractManager\Services',
        'Modules\AssetManagement\Services',
        'Modules\GoogleAdmin\Services',
        'Modules\SoftwareSubscriptions\Services',
        'Modules\ClientPortal\Services',
    ])
    ->not->toUse([
        'App\Http\Controllers',
        'Modules\PIB\Http\Controllers',
        'Modules\Crm\Http\Controllers',
        'Modules\EmailMigration\Http\Controllers',
        'Modules\Payment\Http\Controllers',
        'Modules\ContractManager\Http\Controllers',
        'Modules\AssetManagement\Http\Controllers',
        'Modules\GoogleAdmin\Http\Controllers',
        'Modules\SoftwareSubscriptions\Http\Controllers',
        'Modules\ClientPortal\Http\Controllers',
    ]);
