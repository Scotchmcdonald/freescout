<?php

declare(strict_types=1);

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\UnitTestCase;

class ModuleUnitIsolationGuardTest extends UnitTestCase
{
    /**
     * Temporary allowlist while legacy module unit suites are migrated.
     * Keep this list explicit and shrink it over time.
     *
     * @var array<int, string>
     */
    private array $allowlistedPathPrefixes = [
        'Modules/CaseManager/Tests/Unit/',
        'Modules/Crm/Tests/Unit/',
        'Modules/Alerts/Tests/Unit/',
        'Modules/PIB/Tests/Unit/',
        'Modules/Action1/Tests/Unit/',
    ];

    /**
     * Baseline of known legacy Unit tests still using RefreshDatabase under
     * allowlisted modules. Keep shrinking this list during migrations.
     *
     * @var array<int, string>
     */
    private array $allowlistedRefreshDatabaseBaseline = [
        'Modules/Action1/Tests/Unit/MspScriptServicePestTest.php',
        'Modules/Alerts/Tests/Unit/AlertServicePestTest.php',
        'Modules/Alerts/Tests/Unit/AlertSubscriptionServicePestTest.php',
        'Modules/CaseManager/Tests/Unit/Jobs/CheckCaseApiErrorJobTest.php',
        'Modules/CaseManager/Tests/Unit/Jobs/CheckDiagnosticTimeoutJobTest.php',
        'Modules/CaseManager/Tests/Unit/Jobs/ProcessCompletedDiagnosticsJobTest.php',
        'Modules/CaseManager/Tests/Unit/Jobs/ProcessDiagnosticResultJobTest.php',
        'Modules/CaseManager/Tests/Unit/Listeners/HandleConversationClosedTest.php',
        'Modules/CaseManager/Tests/Unit/Listeners/HandleConversationCreatedTest.php',
        'Modules/CaseManager/Tests/Unit/Listeners/HandleCustomerRepliedTest.php',
        'Modules/CaseManager/Tests/Unit/Listeners/HandleFernConversationCreatedTest.php',
        'Modules/CaseManager/Tests/Unit/Listeners/HandleSplitConfirmationTest.php',
        'Modules/CaseManager/Tests/Unit/Models/CaseRecordHelpersTest.php',
        'Modules/CaseManager/Tests/Unit/Models/CaseRecordTransitionTest.php',
        'Modules/CaseManager/Tests/Unit/Models/DiagnosticTest.php',
        'Modules/CaseManager/Tests/Unit/Models/FernCaseRecordTest.php',
        'Modules/CaseManager/Tests/Unit/Models/PromptLogQuickWinTest.php',
        'Modules/CaseManager/Tests/Unit/Services/AudienceTargetingServiceTest.php',
        'Modules/CaseManager/Tests/Unit/Services/AutomatonUserServiceTest.php',
        'Modules/CaseManager/Tests/Unit/Services/CaseManagerAiServiceTest.php',
        'Modules/CaseManager/Tests/Unit/Services/DecisionEngineProcessTest.php',
        'Modules/CaseManager/Tests/Unit/Services/FernBudgetServiceTest.php',
        'Modules/CaseManager/Tests/Unit/Services/GeminiClientTest.php',
        'Modules/CaseManager/Tests/Unit/Services/KnowledgeEngineTest.php',
        'Modules/CaseManager/Tests/Unit/Services/RmmBridgeServiceTest.php',
        'Modules/CaseManager/Tests/Unit/Traits/AiPipelineFailureHandlerTest.php',
        'Modules/Crm/Tests/Unit/CalculateClientServiceMetricsJobPestTest.php',
        'Modules/Crm/Tests/Unit/ClientTicketServicePestTest.php',
        'Modules/Crm/Tests/Unit/ConversationEventListenerPestTest.php',
        'Modules/Crm/Tests/Unit/CrmEventDispatchPestTest.php',
        'Modules/Crm/Tests/Unit/Models/ClientModelPestTest.php',
        'Modules/Crm/Tests/Unit/Models/ContactModelPestTest.php',
        'Modules/Crm/Tests/Unit/Models/CustomFieldModelPestTest.php',
        'Modules/Crm/Tests/Unit/TicketLifecycleServicePestTest.php',
        'Modules/PIB/Tests/Unit/ClientCreditServicePestTest.php',
        'Modules/PIB/Tests/Unit/MonthEndTimeAggregationJobPestTest.php',
        'Modules/PIB/Tests/Unit/PaymentDisputedListenerPestTest.php',
        'Modules/PIB/Tests/Unit/TimeEntryServicePestTest.php',
    ];

    public function test_module_unit_tests_do_not_use_refresh_database_or_cross_module_persistence(): void
    {
        $unitRoot = base_path('Modules');

        $refreshDatabaseViolations = [];
        $newAllowlistedRefreshDatabaseViolations = [];
        $crossModulePersistenceViolations = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($unitRoot));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            $normalizedPath = str_replace('\\', '/', $relativePath);

            if (! str_contains($normalizedPath, '/Tests/Unit/')) {
                continue;
            }

            $contents = file_get_contents($file->getPathname()) ?: '';
            $usesRefreshDatabase = $this->usesRefreshDatabase($contents);

            if ($this->isAllowlisted($normalizedPath)) {
                if (
                    $usesRefreshDatabase
                    && ! in_array($normalizedPath, $this->allowlistedRefreshDatabaseBaseline, true)
                ) {
                    $newAllowlistedRefreshDatabaseViolations[] = $normalizedPath;
                }

                continue;
            }

            $module = $this->extractModuleName($normalizedPath);
            if ($module === null) {
                continue;
            }

            if ($usesRefreshDatabase) {
                $refreshDatabaseViolations[] = $normalizedPath;
            }

            if ($this->containsCrossModulePersistence($contents, $module)) {
                $crossModulePersistenceViolations[] = $normalizedPath;
            }
        }

        $this->assertSame(
            [],
            $refreshDatabaseViolations,
            "Module unit tests must not use RefreshDatabase unless allowlisted. Violations:\n".implode("\n", $refreshDatabaseViolations)
        );

        $this->assertSame(
            [],
            $newAllowlistedRefreshDatabaseViolations,
            "Allowlisted module Unit tests may only keep known legacy RefreshDatabase files. New violations:\n".implode("\n", $newAllowlistedRefreshDatabaseViolations)
        );

        $this->assertSame(
            [],
            $crossModulePersistenceViolations,
            "Module unit tests must not persist cross-module models directly unless allowlisted. Violations:\n".implode("\n", $crossModulePersistenceViolations)
        );
    }

    private function isAllowlisted(string $path): bool
    {
        foreach ($this->allowlistedPathPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function extractModuleName(string $path): ?string
    {
        if (! preg_match('#^Modules/([^/]+)/#', $path, $matches)) {
            return null;
        }

        return $matches[1] ?? null;
    }

    private function usesRefreshDatabase(string $contents): bool
    {
        return preg_match('/^\s*use\s+\\\\?Illuminate\\\\Foundation\\\\Testing\\\\RefreshDatabase\s*;/m', $contents) === 1
            || preg_match('/^\s*use\s+RefreshDatabase\s*;/m', $contents) === 1
            || preg_match('/RefreshDatabase::class/', $contents) === 1;
    }

    private function containsCrossModulePersistence(string $contents, string $module): bool
    {
        $moduleQuoted = preg_quote($module, '/');

        $pattern = '/Modules\\\\(?!'.$moduleQuoted.'\\\\)[A-Za-z0-9_]+\\\\(?:Models|Entities)\\\\[A-Za-z0-9_]+\s*::\s*(?:create|forceCreate|updateOrCreate|firstOrCreate|factory\s*\(\)\s*->\s*create)\s*\(/m';

        return preg_match($pattern, $contents) === 1;
    }
}
