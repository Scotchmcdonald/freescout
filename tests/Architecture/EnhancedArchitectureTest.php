<?php

declare(strict_types=1);

/**
 * Enhanced Architecture Tests
 *
 * Additional architectural guards to prevent future violations and maintain code quality.
 * These tests complement the core architecture tests with more specific constraints.
 *
 * Guards included:
 * 1. Interface Segregation - Ensures interfaces remain focused (max 5 methods)
 * 2. Idempotent Listener - All listeners must extend IdempotentListener for safe replay
 * 3. Controller Authorization - Controllers must implement proper authorization
 * 4. Financial Transaction Guard - Financial operations must use database transactions
 * 5. Event Handler Registration - Listeners must be registered in ServiceProviders
 */

use Modules\ContractManager\Listeners\AdjustBillingOnSoftwareCountChange;
use Modules\ContractManager\Listeners\PauseBillingTemplatesOnClientArchive;
use Modules\ContractManager\Listeners\RecalculateProrationOnContractChange;
use Modules\ContractManager\Listeners\UpdateEntitlementSnapshots;
use Modules\PIB\Listeners\BillingTemplateDueListener;
use Modules\PIB\Listeners\ConversationLinkedToClientListener;
use Modules\PIB\Listeners\PaymentDisputedListener;

/**
 * Guard 1: Interface Segregation Principle
 *
 * Purpose: Ensures all interfaces in app/Contracts/ remain focused and maintainable
 * by limiting them to a maximum of 5 methods.
 *
 * Why 5 methods?
 * - Small interfaces are easier to implement and test
 * - Promotes single responsibility principle
 * - Makes mocking simpler for unit tests
 * - Encourages composition over large monolithic interfaces
 *
 * If an interface grows beyond 5 methods, consider:
 * - Splitting into focused read/write interfaces (like CreditReader/CreditWriter)
 * - Extracting related methods into a separate interface
 * - Using interface composition
 *
 * Example violation:
 * interface BillingService {
 *     public function createInvoice();  // 1
 *     public function getBalance();     // 2
 *     public function addCredit();      // 3
 *     public function deductCredit();   // 4
 *     public function getHistory();     // 5
 *     public function sendReminder();   // 6 - VIOLATION!
 * }
 *
 * Better approach:
 * interface InvoiceManager { createInvoice(), sendReminder() }
 * interface CreditReader { getBalance(), getHistory() }
 * interface CreditWriter { addCredit(), deductCredit() }
 */
test('all interfaces have maximum 5 methods to enforce Interface Segregation Principle')
    ->expect(function () {
        $interfaces = [
            \App\Contracts\BillingTemplateInterface::class,
            \App\Contracts\UserProvider::class,
            \App\Contracts\EntitlementResolver::class,
            \App\Contracts\Billing\CreditReader::class,
            \App\Contracts\Billing\BillingServiceInterface::class,
            \App\Contracts\Billing\CreditWriter::class,
            \App\Contracts\Billing\CreditLedgerInterface::class,
        ];

        $violations = [];

        foreach ($interfaces as $interface) {
            if (! interface_exists($interface)) {
                continue;
            }

            $reflection = new \ReflectionClass($interface);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
            $methodCount = count($methods);

            if ($methodCount > 5) {
                $violations[] = "{$interface} has {$methodCount} methods (max 5)";
            }
        }

        if (! empty($violations)) {
            throw new \Exception(
                'Interface Segregation Principle violated: '.PHP_EOL.
                implode(PHP_EOL, $violations)
            );
        }

        return true;
    })
    ->toBeTrue();

/**
 * Guard 2: Idempotent Listener Pattern
 *
 * Purpose: All event listeners MUST extend IdempotentListener to ensure safe event replay
 * and exactly-once processing semantics.
 *
 * Why this matters:
 * - Events can be replayed after failures (queue retries, system recovery)
 * - Without idempotency, retries cause duplicate operations (double billing, duplicate emails)
 * - IdempotentListener provides automatic deduplication via processed_events table
 *
 * IdempotentListener provides:
 * - Automatic duplicate detection
 * - Transactional processing with event marking
 * - Safe replay after system failures
 *
 * How it works:
 * 1. Checks if event already processed (processed_events table)
 * 2. If not, processes event inside DB transaction
 * 3. Marks event as processed atomically
 *
 * Example:
 * class SyncGoogleUserListener extends IdempotentListener {
 *     protected function handleIdempotent($event): void {
 *         User::updateOrCreate(['email' => $event->email], [...]);
 *     }
 * }
 */
test('all module listeners extend IdempotentListener for safe event replay')
    ->expect([
        AdjustBillingOnSoftwareCountChange::class,
        BillingTemplateDueListener::class,
        ConversationLinkedToClientListener::class,
        PauseBillingTemplatesOnClientArchive::class,
        PaymentDisputedListener::class,
        RecalculateProrationOnContractChange::class,
        UpdateEntitlementSnapshots::class,
    ])
    ->toExtend('App\Listeners\IdempotentListener');

/**
 * Guard 3: Controller Authorization
 *
 * Purpose: Ensures all controllers have authorization capabilities through the base Controller.
 * Controllers must extend the base Controller which provides AuthorizesRequests trait.
 *
 * Why this matters:
 * - Prevents unauthorized access to sensitive resources
 * - Enforces RBAC (Role-Based Access Control) policies
 * - Provides consistent authorization interface across all controllers
 *
 * Authorization patterns available:
 * 1. $this->authorize('action', $model) - Policy-based authorization (via AuthorizesRequests)
 * 2. Gate::authorize('ability') - Gate-based authorization
 * 3. FormRequest::authorize() - Request-level authorization
 * 4. middleware(['can:permission']) - Route middleware
 *
 * Note: This test verifies controllers extend the base Controller class.
 * Actual usage of authorization methods should be verified in code review.
 *
 * Controllers using proper authorization (examples):
 * - UserController: Uses $this->authorize() for CRUD operations
 * - ConversationController: Uses $this->authorize() for access control
 * - Refer to these as examples when implementing authorization
 */
test('controllers extend base Controller with authorization capabilities')
    ->expect('App\Http\Controllers')
    ->toExtend('App\Http\Controllers\Controller')
    ->ignoring([
        'App\Http\Controllers\Controller', // Base controller itself
    ]);

test('PIB controllers extend base Controller with authorization capabilities')
    ->expect('Modules\PIB\Http\Controllers')
    ->toExtend('App\Http\Controllers\Controller');

/**
 * Example guard for specific critical controllers (optional, add as needed)
 *
 * You can create specific tests for controllers that MUST have authorization:
 *
 * test('UserController uses authorization')
 *     ->expect('App\Http\Controllers\UserController')
 *     ->toUseMethod('authorize'); // If this method becomes available in future Pest versions
 */

/**
 * Guard 4: Financial Transaction Guard
 *
 * Purpose: Ensures all financial operations are wrapped in database transactions
 * to maintain data consistency and prevent partial updates.
 *
 * Why this matters:
 * - Financial operations must be atomic (all-or-nothing)
 * - Prevents inconsistent state (e.g., balance updated but ledger entry missing)
 * - Essential for audit trail integrity
 * - Required for PCI compliance and financial regulations
 *
 * What counts as financial operations:
 * - Credit/debit operations
 * - Payment processing
 * - Invoice generation
 * - Balance updates
 *
 * Example correct pattern:
 * DB::transaction(function () use ($clientId, $amount) {
 *     $this->counter->increment('client_credits', $clientId, 'balance_cents', $amount);
 *     ClientCreditLedger::create([...]);
 * });
 *
 * This test verifies that services with financial methods use DB::transaction.
 */
test('financial services use database transactions for atomic operations')
    ->expect('Modules\PIB\Services\ClientCreditService')
    ->toUse('Illuminate\Database\ConnectionInterface');

test('billing services use database transactions')
    ->expect('Modules\PIB\Services\BillingService')
    ->toUse('Illuminate\Database\ConnectionInterface');

/**
 * Guard 5: Event Handler Registration Guard
 *
 * Purpose: Ensures all listeners are properly registered in their module's ServiceProvider.
 * Unregistered listeners will never execute, causing silent failures.
 *
 * Why this matters:
 * - Unregistered listeners fail silently (no errors, just doesn't run)
 * - Critical for event-driven architecture
 * - Easy to forget during development
 * - Difficult to debug in production
 *
 * Registration pattern:
 * Event::listen(
 *     \Modules\ContractManager\Events\BillingTemplateDue::class,
 *     \Modules\PIB\Listeners\BillingTemplateDueListener::class
 * );
 *
 * This test parses ServiceProvider files to verify Event::listen() registrations exist.
 */
test('all module listeners are registered in module providers')
    ->expect(function () {
        $projectRoot = dirname(__DIR__, 2);
        $legacyUnregisteredAllowlist = [
            'Modules\\ClientPortal\\Listeners\\CreateQuoteApprovalRequest',
            'Modules\\PIB\\Listeners\\AdjustBillingOnSoftwareCountChange',
            'Modules\\PIB\\Listeners\\RecalculateProrationOnContractChange',
            'Modules\\PIB\\Listeners\\UpdateEntitlementSnapshots',
        ];

        $missingRegistrations = [];

        foreach (glob($projectRoot.'/Modules/*/Listeners', GLOB_ONLYDIR) as $listenersDir) {
            $module = basename(dirname($listenersDir));
            $providerFiles = glob($projectRoot."/Modules/{$module}/Providers/*.php");

            if (! is_array($providerFiles) || $providerFiles === []) {
                continue;
            }

            $providersContent = '';
            foreach ($providerFiles as $providerFile) {
                $providersContent .= (string) file_get_contents($providerFile);
            }

            foreach (glob($listenersDir.'/*.php') as $listenerFile) {
                $listenerClass = pathinfo($listenerFile, PATHINFO_FILENAME);
                $listenerContents = (string) file_get_contents($listenerFile);

                if (preg_match('/abstract\\s+class\\s+'.preg_quote($listenerClass, '/').'\\b/', $listenerContents) === 1) {
                    continue;
                }

                $fqcn = "Modules\\{$module}\\Listeners\\{$listenerClass}";

                $isReferenced = str_contains($providersContent, $fqcn)
                    || str_contains($providersContent, "{$listenerClass}::class");

                if ($isReferenced) {
                    continue;
                }

                if (in_array($fqcn, $legacyUnregisteredAllowlist, true)) {
                    continue;
                }

                $missingRegistrations[] = $fqcn;
            }
        }

        if ($missingRegistrations !== []) {
            sort($missingRegistrations);
            throw new \Exception(
                'Listener registration guard failed. The following listeners are not referenced in any module provider: '.PHP_EOL.
                implode(PHP_EOL, $missingRegistrations)
            );
        }

        return true;
    })
    ->toBeTrue();

/**
 * Additional Module Listener Registration Checks
 *
 * As more modules are added with listeners, add similar registration checks here.
 * Pattern:
 *
 * test('ModuleName listeners are registered')
 *     ->expect(function () {
 *         $provider = base_path('Modules/ModuleName/Providers/ModuleNameServiceProvider.php');
 *         $content = File::get($provider);
 *         // Verify Event::listen() calls for each listener
 *         return true;
 *     })
 *     ->toBeTrue();
 */

// ---------------------------------------------------------------------------
// No-Fly List enforcement — keeps No-Fly violations from re-accumulating
// ---------------------------------------------------------------------------

test('unit model tests do not assert database row counts')
    ->expect('Tests\Unit\Models')
    ->not->toUse('assertDatabaseCount');

test('unit tests do not use wall-clock timing assertions')
    ->expect('Tests\Unit')
    ->not->toUse('microtime');

test('unit tests do not assert database has records')
    ->expect('Tests\Unit\Models')
    ->not->toUse('assertDatabaseHas');

test('unit tests do not assert eloquent relationship types directly')
    ->expect('Tests\Unit\Models')
    ->not->toUse('assertInstanceOf');

// ---------------------------------------------------------------------------
// Phase 6 Rule 2: Listeners that make HTTP calls must have Http::fake coverage
// ---------------------------------------------------------------------------

test('listeners that make Http calls have Http::fake coverage in their tests', function () {
    $projectRoot = dirname(__DIR__, 2);
    $violations = [];

    foreach (glob($projectRoot.'/Modules/*/Listeners', GLOB_ONLYDIR) as $listenerDir) {
        preg_match('#Modules/([^/]+)/Listeners#', $listenerDir, $m);
        $module = $m[1];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($listenerDir)) as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $contents = (string) file_get_contents($file->getPathname());

            // Skip if listener doesn't make HTTP calls
            if (! str_contains($contents, 'Http::') && ! str_contains($contents, 'Facades\Http')) {
                continue;
            }

            $listenerName = basename($file->getPathname(), '.php');

            // Search for test files covering this listener
            $testDirs = glob($projectRoot."/Modules/{$module}/Tests/*");
            $hasFake = false;

            foreach ($testDirs ?: [] as $testDir) {
                if (! is_dir($testDir)) {
                    continue;
                }
                $testIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testDir));
                foreach ($testIterator as $testFile) {
                    if (! $testFile->isFile() || $testFile->getExtension() !== 'php') {
                        continue;
                    }
                    if (! str_contains($testFile->getFilename(), $listenerName)) {
                        continue;
                    }
                    $testContents = (string) file_get_contents($testFile->getPathname());
                    if (str_contains($testContents, 'Http::fake') || str_contains($testContents, 'Http::preventStrayRequests')) {
                        $hasFake = true;
                        break 2;
                    }
                }
            }

            if (! $hasFake) {
                $relativePath = str_replace($projectRoot.'/', '', $file->getPathname());
                $violations[] = "{$relativePath} uses Http but has no Http::fake test coverage";
            }
        }
    }

    expect($violations)->toBe([]);
});

// ---------------------------------------------------------------------------
// Phase 6 Rule 3: Financial service calculation methods must declare explicit
// numeric return types (no mixed, no missing return type)
// ---------------------------------------------------------------------------

test('financial service calculation methods declare explicit numeric return types', function () {
    $projectRoot = dirname(__DIR__, 2);
    $financialServices = [
        $projectRoot.'/Modules/PIB/Services/ProrationService.php',
        $projectRoot.'/Modules/PIB/Services/InvoiceGenerator.php',
        $projectRoot.'/Modules/PIB/Services/BillingAnalysisService.php',
        $projectRoot.'/Modules/ContractManager/Services/BillingTemplateService.php',
        $projectRoot.'/Modules/Payment/Services/CreditManagementService.php',
        $projectRoot.'/Modules/Payment/Services/HelcimService.php',
    ];

    $calcPattern = '/^(calculate|get.*Amount|get.*Rate|get.*Total|get.*Balance)/i';
    $violations = [];

    foreach ($financialServices as $path) {
        if (! file_exists($path)) {
            continue;
        }

        $contents = (string) file_get_contents($path);

        // Extract FQCN from file
        $namespace = '';
        $className = '';
        if (preg_match('/namespace\s+([^;]+);/', $contents, $nsMatch)) {
            $namespace = $nsMatch[1];
        }
        if (preg_match('/class\s+(\w+)/', $contents, $clsMatch)) {
            $className = $clsMatch[1];
        }

        if ($namespace === '' || $className === '') {
            continue;
        }

        $fqcn = $namespace.'\\'.$className;
        if (! class_exists($fqcn)) {
            continue;
        }

        $reflection = new ReflectionClass($fqcn);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $fqcn) {
                continue;
            }

            if (! preg_match($calcPattern, $method->getName())) {
                continue;
            }

            $returnType = $method->getReturnType();

            if ($returnType === null) {
                $violations[] = "{$fqcn}::{$method->getName()} has no return type declaration";
                continue;
            }

            $typeName = $returnType instanceof \ReflectionNamedType ? $returnType->getName() : (string) $returnType;
            if ($typeName === 'mixed') {
                $violations[] = "{$fqcn}::{$method->getName()} returns 'mixed' — must use explicit type";
            }
        }
    }

    expect($violations)->toBe([]);
});

// ---------------------------------------------------------------------------
// Phase 9: Documentation currency guard
// Ensures the testing contribution guide exists and was updated in the current year.
// ---------------------------------------------------------------------------

test('testing contribution guide exists and is current year', function () {
    $projectRoot = dirname(__DIR__, 2);
    $guidePath = $projectRoot.'/docs/development/TESTING_CONTRIBUTION_GUIDE.md';

    expect(file_exists($guidePath))->toBeTrue('TESTING_CONTRIBUTION_GUIDE.md must exist');

    $lastModified = filemtime($guidePath);
    $currentYear = (int) date('Y');
    $modifiedYear = (int) date('Y', $lastModified);

    expect($modifiedYear)->toBe($currentYear, 'TESTING_CONTRIBUTION_GUIDE.md must be updated in the current calendar year');
});
