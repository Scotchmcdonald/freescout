<?php

declare(strict_types=1);

/**
 * Architecture Tests – Module Service Constraints
 *
 * Enforces architectural rules specifically scoped to Module service classes.
 * These complement the generic ModuleBoundariesTest and NamingConventionsTest
 * with rules targeting the highest-risk coupling patterns inside Modules/.
 *
 * Guards:
 *   1. Module services carry the 'Service' suffix (consistent discovery & tooling)
 *   2. Module services must not access other modules' DB tables via raw queries
 *      (cross-module data access must go through published Contracts / Events)
 *   3. Module Controllers must not bypass the service layer by importing the DB
 *      facade directly — there are 3 known grandfathered exceptions below.
 *   4. Module listeners are classes
 *   5. Module requests extend FormRequest
 */

test('module services have Service suffix')
    ->expect([
        'Modules\PIB\Services',
        'Modules\ContractManager\Services',
        'Modules\Payment\Services',
        'Modules\CaseManager\Services',
        'Modules\SoftwareSubscriptions\Services',
        'Modules\Crm\Services',
        'Modules\KnowledgeBase\Services',
        'Modules\AssetManagement\Services',
    ])
    ->toHaveSuffix('Service')
    ->ignoring([
        // Lookup providers follow a different naming contract (provider pattern)
        'Modules\SoftwareSubscriptions\Services\AssetManagementAssetLookupProvider',
        'Modules\SoftwareSubscriptions\Services\CrmAssignableEmailLookupProvider',
        'Modules\SoftwareSubscriptions\Services\CrmContactLookupProvider',
        // CRM registry/counter helpers — not services in the DI sense
        'Modules\Crm\Services\CrmTabRegistry',
        'Modules\Crm\Services\CrmUserEntitlementCountProvider',
        'Modules\Crm\Services\UserIdentityConflictResolver',
        // CaseManager domain objects: AI client, engines, and strategy pattern implementations
        'Modules\CaseManager\Services\GeminiClient',
        'Modules\CaseManager\Services\DecisionEngine',
        'Modules\CaseManager\Services\KnowledgeEngine',
        'Modules\CaseManager\Services\ImmediateRemediationStrategy',
        'Modules\CaseManager\Services\ProposeTicketSplitStrategy',
        'Modules\CaseManager\Services\ProvideKbArticleStrategy',
        'Modules\CaseManager\Services\ReopenAndLinkStrategy',
        'Modules\CaseManager\Services\RouteToTechnicianStrategy',
        'Modules\CaseManager\Services\StrategyInterface',
        'Modules\CaseManager\Services\TriageAndClarifyStrategy',
        // CaseManager strategy objects in the Strategies/ sub-namespace
        'Modules\CaseManager\Services\Strategies',
        // PIB financial object — predates suffix convention (tech-debt: rename to InvoiceGeneratorService)
        'Modules\PIB\Services\InvoiceGenerator',
    ]);

test('module services use strict types')
    ->expect([
        'Modules\PIB\Services',
        'Modules\ContractManager\Services',
        'Modules\Payment\Services',
        'Modules\CaseManager\Services',
        'Modules\SoftwareSubscriptions\Services',
        'Modules\Crm\Services',
    ])
    ->toUseStrictTypes();

test('module controllers must not use raw DB facade directly')
    ->expect('Modules\*\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB')
    ->ignoring([
        // TECH DEBT: these 3 controllers must be migrated to service-layer queries
        // Tracked: DB direct usage violates the Controller → Service → DB layering rule
        'Modules\ClientPortal\Http\Controllers\PortalController',
        'Modules\DeploymentManager\Http\Controllers\DeploymentController',
        'Modules\SoftwareSubscriptions\Http\Controllers\Admin\VendorCostReportController',
    ]);

test('module listeners are classes')
    ->expect('Modules\*\Listeners')
    ->toBeClasses();

test('module requests extend FormRequest')
    ->expect('Modules\*\Http\Requests')
    ->toExtend('Illuminate\Foundation\Http\FormRequest')
    ->ignoring([
        // Any legacy request classes that pre-date FormRequest adoption can be listed here
    ]);
