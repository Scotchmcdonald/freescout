<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\Action1ScriptCallbackController;
use App\Http\Controllers\Controller;
use App\Services\CircuitBreakerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\SystemDiagnosticsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Action1\Contracts\Action1ManageClient;
use Modules\Action1\Contracts\Action1RunClient;
use Modules\Action1\Contracts\Action1SyncClient;
use Modules\Action1\Enums\Action1Role;
use Modules\Action1\Enums\MspScriptCategory;
use Modules\Action1\Services\MspScriptService;
use Modules\CaseManager\Services\GeminiClient;
use Modules\GoogleAdmin\Models\GoogleConfig;
use Modules\GoogleAdmin\Services\GoogleWorkspaceService;
use Nwidart\Modules\Facades\Module as ModuleFacade;

/**
 * Admin controller for Infrastructure Resilience monitoring.
 * Phase 6: Read-only dashboards for Circuit Breaker and Rate Limiter services.
 */
class ResilienceController extends Controller
{
    public function __construct(private readonly SystemDiagnosticsService $diagnostics) {}

    /**
     * Combined Resilience Dashboard - Circuit Breakers & Rate Limiters
     */
    public function index(): View
    {
        $enabledModules = $this->enabledModuleAliases();
        $apiIntegrations = $this->installedApiIntegrations($enabledModules);

        // --- Circuit Breaker Logic ---
        $breaker = app(CircuitBreakerService::class);
        $cbServices = array_values(array_unique(array_map(
            fn (array $api): string => is_string($api['circuit_service']) ? $api['circuit_service'] : '',
            $apiIntegrations
        )));
        $allStates = collect($breaker->getAllStates())->keyBy('service');
        $circuitBreakerStatus = [];
        $openCircuits = 0;

        foreach ($cbServices as $service) {
            $state = $allStates->get($service);

            if (! $state) {
                $state = (object) [
                    'service' => $service,
                    'state' => 'closed',
                    'failure_count' => 0,
                    'last_failure_at' => null,
                    'opened_at' => null,
                ];
            }

            $circuitBreakerStatus[] = [
                'key' => $service,
                'name' => $this->formatServiceName($service),
                'state' => $state->state,
                'failure_count' => $state->failure_count ?? 0,
                'last_checked_at' => $state->opened_at,
                'last_checked_human' => $state->opened_at ? \Carbon\Carbon::parse($state->opened_at)->diffForHumans() : 'Never',
                'next_retry' => $state->opened_at ? \Carbon\Carbon::parse($state->opened_at)->addMinutes(5) : null,
                'can_retry' => $state->state === 'half_open' || ($state->state === 'open' && $state->opened_at && \Carbon\Carbon::parse($state->opened_at)->addMinutes(5)->isPast()),
            ];

            if ($state->state === 'open') {
                $openCircuits++;
            }
        }

        // --- Rate Limiter Logic ---
        $rateLimitStatus = $this->buildRateLimitStatus($apiIntegrations);

        // --- API Health Matrix (module-aware) ---
        $apiHealthChecks = array_map(function (array $api) use ($allStates, $rateLimitStatus): array {
            $state = $allStates->get(is_string($api['circuit_service']) ? $api['circuit_service'] : '');
            $rate = collect($rateLimitStatus)->firstWhere('api_key', $api['key']);

            return [
                'key' => $api['key'],
                'name' => $api['name'],
                'module' => $api['module_name'],
                'description' => $api['description'],
                'probe_url' => route('admin.resilience.api-probe', ['api' => $api['key']]),
                'primary_probe_label' => $api['primary_probe_label'] ?? 'Run Connectivity Test',
                'secondary_probe_label' => $api['secondary_probe_label'] ?? null,
                'secondary_probe_mode' => $api['secondary_probe_mode'] ?? null,
                'supports_deep_test' => $api['key'] === 'action1',
                'circuit' => [
                    'service' => $api['circuit_service'],
                    'state' => $state !== null ? ($state->state ?? 'closed') : 'closed',
                    'failure_count' => $state !== null ? (int) ($state->failure_count ?? 0) : 0,
                    'last_checked_human' => $state !== null && isset($state->opened_at) && $state->opened_at
                        ? \Carbon\Carbon::parse((string) $state->opened_at)->diffForHumans()
                        : 'Never',
                ],
                'rate_limit' => is_array($rate) ? $rate : null,
            ];
        }, $apiIntegrations);

        // --- Action1 Role Configuration Status ---
        $action1Roles = [];
        foreach (Action1Role::cases() as $role) {
            $key = $role->configKey();
            $configured = ! empty(config("action1.roles.{$key}.client_id"))
                && ! empty(config("action1.roles.{$key}.client_secret"));
            $action1Roles[] = [
                'role' => $role->value,
                'label' => $role->label(),
                'description' => $role->description(),
                'configured' => $configured,
                'probe_url' => route('admin.resilience.action1-probe', ['role' => $role->value]),
            ];
        }

        return view('admin.resilience.index', [
            'circuitBreakers' => $circuitBreakerStatus,
            'openCircuits' => $openCircuits,
            'rateLimits' => $rateLimitStatus,
            'apiHealthChecks' => $apiHealthChecks,
            'action1Roles' => $action1Roles,
            'action1TestProbe' => [
                'org_id' => config('action1.test_probe.org_id'),
                'endpoint_name' => config('action1.test_probe.endpoint_name'),
                'group_name' => config('action1.test_probe.group_name'),
                'configured' => ! empty(config('action1.test_probe.org_id')),
            ],
            'hasAction1Api' => collect($apiHealthChecks)->contains('key', 'action1'),
        ]);
    }

    /**
     * Reset a circuit breaker (dangerous action - requires confirmation).
     *
     * @param  string  $service  Service name (google_api, action1_api, helcim_api)
     */
    public function resetCircuit(string $service): RedirectResponse
    {
        // Validate service name
        $allowedServices = ['google_api', 'action1_api', 'helcim_api', 'gemini_api'];
        if (! in_array($service, $allowedServices, true)) {
            return redirect()->back()->with('error', 'Invalid service name.');
        }

        // Reset the circuit
        $breaker = app(CircuitBreakerService::class);
        $breaker->reset($service);

        $serviceName = $this->formatServiceName($service);

        return redirect()->back()->with('success', "Circuit breaker for {$serviceName} has been reset. Service will be tested on next request.");
    }

    /**
     * Event Audit Log - Terminal-style view of system events.
     *
     * Provides full visibility into the event bus:
     * - Filtering by event type, channel, date
     * - Full-text search on JSON payload
     * - Monospace terminal-like display for payloads
     */
    public function eventsAudit(\Illuminate\Http\Request $request): View
    {
        $filters = $request->only(['search', 'event_type', 'date_from', 'date_to']);
        $events = $this->diagnostics->getPolycastEvents($filters);

        return view('admin.resilience.events-audit', [
            'events' => $events,
            'filters' => $filters,
        ]);
    }

    /**
     * Export events to CSV.
     */
    public function exportEvents(\Illuminate\Http\Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filters = $request->only(['search', 'event_type', 'date_from', 'date_to']);

        return response()->streamDownload(function () use ($filters) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fputcsv($handle, ['ID', 'Channel', 'Event', 'Payload', 'Timestamp']);

            $this->diagnostics->streamPolycastEventsCsv($filters, function (object $event) use ($handle): void {
                fputcsv($handle, [
                    $event->id,
                    $event->channel,
                    $event->event,
                    $event->payload,
                    $event->created_at,
                ]);
            });

            fclose($handle);
        }, 'events-audit-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Live connectivity probe for one Action1 API role.
     *
     * Attempts OAuth2 authentication and (for the Sync role) a lightweight
     * organisations list call to confirm the API is reachable end-to-end.
     * Always invalidates the cached token first so we test a fresh auth.
     *
     * Returns JSON: { ok: bool, role: string, message: string, latency_ms: int }
     */
    public function probeAction1(string $role): JsonResponse
    {
        $allowed = array_column(Action1Role::cases(), 'value');
        if (! in_array($role, $allowed, true)) {
            return response()->json(['ok' => false, 'role' => $role, 'message' => 'Unknown role.'], 400);
        }

        return response()->json($this->probeAction1Role(Action1Role::from($role)), 200);
    }

    /**
     * Lightweight API probe for installed integrations.
     */
    public function probeApi(Request $request, string $api): JsonResponse
    {
        $allowedApis = collect($this->installedApiIntegrations($this->enabledModuleAliases()))
            ->pluck('key')
            ->all();
        $mode = $request->string('mode')->toString();

        if (! in_array($api, $allowedApis, true)) {
            return response()->json([
                'ok' => false,
                'api' => $api,
                'message' => 'API probe not available for this integration.',
            ], 404);
        }

        return match ($api) {
            'action1' => $this->probeAction1Summary(),
            'google_workspace' => $mode === 'tenant_sweep'
                ? $this->probeGoogleWorkspaceTenantSweep()
                : $this->probeGoogleWorkspaceHomeDomain(),
            'helcim' => $this->probeHelcim(),
            'gemini_api' => $this->probeGemini(),
            default => response()->json([
                'ok' => false,
                'api' => $api,
                'message' => 'No probe implemented for this API.',
            ], 400),
        };
    }

    /**
     * Sequential Action1 end-to-end probe.
     *
     * Executed one step at a time from the browser stepper UI.
     * Steps: sync | manage_create | run | run_status | manage_cleanup
     */
    public function probeAction1Sequence(Request $request, string $step): JsonResponse
    {
        $allowedSteps = ['sync', 'manage_create', 'run', 'run_status', 'manage_cleanup'];
        if (! in_array($step, $allowedSteps, true)) {
            return response()->json(['ok' => false, 'message' => 'Unknown step.'], 400);
        }

        $testOrgId = config()->string('action1.test_probe.org_id', '');
        $testEpName = config()->string('action1.test_probe.endpoint_name', '');
        $start = microtime(true);

        try {
            // ── Step 1: Sync — verify test endpoint is reachable ───────────────
            if ($step === 'sync') {
                if (empty($testOrgId) || empty($testEpName)) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'ACTION1_TEST_ORG_ID or ACTION1_TEST_ENDPOINT_NAME not set in .env',
                    ]);
                }

                cache()->forget(Action1Role::Sync->tokenCacheKey());
                $syncService = app(Action1SyncClient::class);
                $endpoints = $syncService->listEndpoints($testOrgId);
                $match = collect($endpoints)
                    ->first(fn (array $ep): bool => strcasecmp(is_string($ep['name']) ? $ep['name'] : '', $testEpName) === 0);

                if ($match === null) {
                    return response()->json([
                        'ok' => false,
                        'message' => "Endpoint '{$testEpName}' not found in org {$testOrgId}. Found ".count($endpoints).' endpoint(s).',
                        'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                    ]);
                }

                return response()->json([
                    'ok' => true,
                    'message' => "Endpoint '{$testEpName}' found and reachable.",
                    'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                    'endpoint_id' => $match['id'] ?? null,
                    'endpoint_status' => $match['status'] ?? 'unknown',
                    'endpoint_name' => $match['name'] ?? $testEpName,
                ]);
            }

            // ── Step 2: Manage — delete stale + create fresh msp_dx_ApiTest ───
            if ($step === 'manage_create') {
                cache()->forget(Action1Role::ScriptManager->tokenCacheKey());
                $mspService = app(MspScriptService::class);

                // Remove any pre-existing copy so we always start clean
                $existing = $mspService->list(true)
                    ->first(fn (array $s): bool => strtolower(is_string($s['name']) ? $s['name'] : '') === 'msp_dx_apitest');

                $recycled = false;
                if ($existing !== null) {
                    /** @var array<string, mixed> $existing */
                    $mspService->delete(is_string($existing['id']) ? $existing['id'] : '');
                    $recycled = true;
                }

                $script = $mspService->create(
                    MspScriptCategory::Diagnostic,
                    'ApiTest',
                    [
                        'description' => 'Read-only API connectivity canary. Collects basic system info and POSTs to a callback URL. Created by resilience probe — safe to delete.',
                        'platform' => 'Windows',
                        'language' => 'Command',
                        'script_text' => implode("\r\n", [
                            '@echo off',
                            '',
                            'REM msp_dx_ApiTest -- read-only resilience probe',
                            'REM CallbackUrl is injected at dispatch time via run_script_params.',
                            'REM Values are sent as query parameters to avoid CMD JSON-generation issues.',
                            '',
                            'curl.exe -s --max-time 30 -X POST "%CallbackUrl%?status=OK&host=%COMPUTERNAME%&user=%USERNAME%"',
                        ]),
                        'params' => [
                            [
                                'name' => 'CallbackUrl',
                                'type' => 'String',
                                'default' => '',
                            ],
                        ],
                    ]
                );

                // Action1 has a propagation delay between script creation and the script
                // becoming visible to the automation runner. Poll getScript() until it
                // returns the newly created record — or time out after ~15 seconds.
                $scriptId = is_string($script['id'] ?? null) ? $script['id'] : '';
                $manageClient = app(Action1ManageClient::class);
                $confirmed = false;
                $maxPropagationAttempts = 15;
                for ($i = 0; $i < $maxPropagationAttempts; $i++) {
                    if ($i > 0) {
                        sleep(1);
                    }
                    if ($scriptId !== '' && $manageClient->getScript($scriptId) !== null) {
                        $confirmed = true;
                        break;
                    }
                }

                if (! $confirmed) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Script was created but did not become available in Action1 within 15 seconds. Try again.',
                        'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                        'script_id' => $scriptId ?: null,
                    ]);
                }

                return response()->json([
                    'ok' => true,
                    'message' => ($recycled ? 'Stale copy removed and r' : 'R').'ecreated msp_dx_ApiTest successfully.',
                    'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                    'script_id' => $scriptId ?: null,
                    'script_name' => $script['name'] ?? 'msp_dx_ApiTest',
                    'recycled' => $recycled,
                ]);
            }

            // ── Step 3: Run — dispatch msp_dx_ApiTest on the test endpoint ─────
            // Mints a one-time callback token and passes the URL to the script via
            // run_script_params. Action1 substitutes %CallbackUrl% at run time because
            // the param is declared in the script definition's params array.
            if ($step === 'run') {
                $endpointId = $request->string('endpoint_id')->toString();
                $scriptId = $request->string('script_id')->toString();

                if (empty($testOrgId) || $endpointId === '' || $scriptId === '') {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Missing endpoint_id or script_id in request body.',
                    ]);
                }

                // Mint a one-time callback token and store the pending record BEFORE
                // dispatching so the cache key exists the moment the endpoint phones home.
                $token = Str::random(40);
                $callbackUrl = route('action1.script-callback', ['token' => $token]);

                cache()->put(
                    Action1ScriptCallbackController::CACHE_PREFIX.$token,
                    [
                        'status' => 'pending',
                        'script_id' => $scriptId,
                        'org_id' => $testOrgId,
                        'minted_at' => now()->toIso8601String(),
                    ],
                    Action1ScriptCallbackController::TOKEN_TTL
                );

                $runService = app(Action1RunClient::class);
                $automation = $runService->runScript(
                    orgId: $testOrgId,
                    endpointId: $endpointId,
                    scriptId: $scriptId,
                    description: 'Resilience probe — '.now()->toIso8601String(),
                    parameters: [['name' => 'CallbackUrl', 'value' => $callbackUrl, 'type' => 'String']],
                );

                $automationId = is_string($automation['id'] ?? null) ? $automation['id'] : null;

                // Backfill automation_id so run_status can use it for failure detection.
                if ($automationId !== null) {
                    $existing = cache()->get(Action1ScriptCallbackController::CACHE_PREFIX.$token, []);
                    cache()->put(
                        Action1ScriptCallbackController::CACHE_PREFIX.$token,
                        array_merge(is_array($existing) ? $existing : [], ['automation_id' => $automationId]),
                        Action1ScriptCallbackController::TOKEN_TTL
                    );
                }

                return response()->json([
                    'ok' => true,
                    'message' => 'Script dispatched. Waiting for endpoint to phone home.',
                    'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                    'automation_id' => $automationId,
                    'callback_token' => $token,
                    'run_script_id' => $scriptId,
                    'debug_payload' => $automation['_dispatched_payload'] ?? null,
                ]);
            }

            // ── Step 4: Run status — primary: cache phone-home; secondary: Action1 failure check ──
            // The script POSTs to our callback URL. We poll the cache for the received record.
            // Every 10 attempts we also ask Action1 for the endpoint result to detect hard failures
            // (e.g. script errored before curl could fire, endpoint offline, etc.).
            if ($step === 'run_status') {
                $token = $request->string('callback_token')->toString();

                if ($token === '') {
                    return response()->json(['ok' => false, 'message' => 'Missing callback_token.']);
                }

                /** @var array{status: string, output?: string, host?: string, received_at?: string, automation_id?: string, org_id?: string}|null $record */
                $record = cache()->get(Action1ScriptCallbackController::CACHE_PREFIX.$token);

                if ($record === null) {
                    return response()->json([
                        'ok' => false,
                        'pending' => false,
                        'message' => 'Callback token expired or not found.',
                        'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                    ]);
                }

                $received = ($record['status'] ?? '') === 'received';

                if ($received) {
                    // Clean up the automation schedule now that we have confirmation.
                    if (isset($record['automation_id'], $record['org_id'])) {
                        try {
                            app(Action1RunClient::class)->deleteAutomation(
                                (string) $record['org_id'],
                                (string) $record['automation_id'],
                            );
                        } catch (\Throwable) {
                        }
                    }

                    $endpointStatus = (string) ($record['endpoint_status'] ?? 'OK');
                    $phoneHomeOk = strtoupper($endpointStatus) === 'OK';

                    $phoneHomePayload = [
                        'status' => $endpointStatus,
                        'host' => $record['host'] ?? null,
                        'user' => $record['user'] ?? null,
                        'received_at' => $record['received_at'] ?? null,
                    ];

                    return response()->json([
                        'ok' => $phoneHomeOk,
                        'pending' => false,
                        'message' => $phoneHomeOk
                            ? 'Endpoint phoned home successfully.'
                            : 'Endpoint phoned home but reported status: '.$endpointStatus,
                        'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                        'phone_home_payload' => $phoneHomePayload,
                    ]);
                }

                // Not yet received — every 10th poll check Action1 for hard failures.
                $attempt = $request->integer('attempt', 0);
                if ($attempt > 0 && $attempt % 10 === 0 && isset($record['automation_id'], $record['org_id'])) {
                    try {
                        $runService = app(Action1RunClient::class);
                        $resultsPage = $runService->getEndpointResults(
                            (string) $record['org_id'],
                            (string) $record['automation_id'],
                        );
                        /** @var list<array<string, mixed>> $items */
                        $items = is_array($resultsPage['items'] ?? null) ? $resultsPage['items'] : [];
                        if (! empty($items)) {
                            $firstItem = is_array($items[0]) ? $items[0] : [];
                            $epStatus = strtolower(is_string($firstItem['status'] ?? null) ? ($firstItem['status'] ?? '') : '');
                            // Action1 endpoint-result status enum: Pending, Running, Stopped, Success, Warning, Error
                            $terminalFailures = ['error', 'warning', 'stopped', 'failed', 'cancelled', 'aborted'];
                            if (in_array($epStatus, $terminalFailures, true)) {
                                try {
                                    $runService->deleteAutomation(
                                        (string) $record['org_id'],
                                        (string) $record['automation_id'],
                                    );
                                } catch (\Throwable) {
                                }

                                return response()->json([
                                    'ok' => false,
                                    'pending' => false,
                                    'message' => 'Script failed before phoning home (Action1 status: '.$epStatus.').',
                                    'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                                    'action1_status' => $epStatus,
                                    'description' => is_string($firstItem['description'] ?? null) ? $firstItem['description'] : null,
                                ]);
                            }
                        }
                    } catch (\Throwable) {
                        // Non-fatal — keep waiting for phone-home if Action1 check fails.
                    }
                }

                return response()->json([
                    'ok' => false,
                    'pending' => true,
                    'message' => 'Waiting for endpoint to phone home.',
                    'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                ]);
            }

            // ── Step 5: Manage — delete msp_dx_ApiTest ────────────────────────
            $scriptId = $request->string('script_id')->toString();

            if ($scriptId === '') {
                return response()->json(['ok' => false, 'message' => 'Missing script_id.']);
            }

            $mspService = app(MspScriptService::class);
            $mspService->delete($scriptId);

            return response()->json([
                'ok' => true,
                'message' => 'msp_dx_ApiTest deleted. Environment is clean.',
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            ]);
        } catch (\Throwable $e) {
            logger()->warning('Action1 sequence probe failed', ['step' => $step, 'error' => $e->getMessage()]);

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            ], 200);
        }
    }

    /**
     * Format service name for display.
     */

    /**
     * Format service name for display.
     */
    private function formatServiceName(string $service): string
    {
        return match ($service) {
            'google_workspace', 'google_api' => 'Google Workspace API',
            'action1', 'action1_api' => 'Action1 RMM API',
            'helcim', 'helcim_api' => 'Helcim Payment Gateway',
            'gemini_api' => 'Gemini AI API',
            default => ucwords(str_replace('_', ' ', $service)),
        };
    }

    /**
     * @return array<int, string>
     */
    private function enabledModuleAliases(): array
    {
        $aliases = [];

        try {
            foreach (ModuleFacade::allEnabled() as $module) {
                if (! is_object($module)) {
                    continue;
                }
                if (method_exists($module, 'getAlias')) {
                    $aliases[] = strtolower((string) $module->getAlias());
                }
                if (method_exists($module, 'getName')) {
                    $aliases[] = strtolower((string) $module->getName());
                }
            }
        } catch (\Throwable) {
            $statusFile = config('modules.activators.file.statuses-file', base_path('modules_statuses.json'));
            if (is_string($statusFile) && is_file($statusFile)) {
                $decoded = json_decode((string) file_get_contents($statusFile), true);
                if (is_array($decoded)) {
                    foreach ($decoded as $module => $enabled) {
                        if ($enabled) {
                            $aliases[] = strtolower((string) $module);
                        }
                    }
                }
            }
        }

        return array_values(array_unique($aliases));
    }

    /**
     * @param  array<int, string>  $enabledModules
     * @return array<int, array<string, mixed>>
     */
    private function installedApiIntegrations(array $enabledModules): array
    {
        $catalog = [
            [
                'key' => 'action1',
                'name' => 'Action1 API',
                'module_name' => 'Action1',
                'module_aliases' => ['action1', 'assetmanagement', 'deploymentmanager', 'casemanager'],
                'circuit_service' => 'action1',
                'rate_prefix' => 'action1_api:',
                'limit' => 5000,
                'description' => 'RMM sync and automation execution API.',
                'primary_probe_label' => 'Run Connectivity Test',
            ],
            [
                'key' => 'google_workspace',
                'name' => 'Google Workspace API',
                'module_name' => 'GoogleAdmin',
                'module_aliases' => ['googleadmin', 'assetmanagement', 'clientportal'],
                'circuit_service' => 'google_workspace',
                'rate_prefix' => 'google_api:',
                'limit' => config()->integer('google.rate_limit', 100),
                'description' => 'Directory and device synchronization API.',
                'primary_probe_label' => 'Run Home Domain Test',
                'secondary_probe_label' => 'Run Tenant Sweep',
                'secondary_probe_mode' => 'tenant_sweep',
            ],
            [
                'key' => 'helcim',
                'name' => 'Helcim Payment API',
                'module_name' => 'Payment',
                'module_aliases' => ['payment', 'pib', 'clientportal'],
                'circuit_service' => 'helcim',
                'rate_prefix' => 'helcim_api:',
                'limit' => null,
                'description' => 'Vaulting, charge, and payment settlement API.',
                'primary_probe_label' => 'Run Connectivity Test',
            ],
            [
                'key' => 'gemini_api',
                'name' => 'Gemini AI API',
                'module_name' => 'CaseManager',
                'module_aliases' => ['casemanager'],
                'circuit_service' => 'gemini_api',
                'rate_prefix' => 'gemini_api_hourly',
                'limit' => 1500,
                'description' => 'AI analysis and response generation API.',
                'primary_probe_label' => 'Run Connectivity Test',
            ],
        ];

        return array_values(array_filter($catalog, function (array $api) use ($enabledModules): bool {
            $aliases = $api['module_aliases'];

            foreach ($aliases as $alias) {
                if (in_array($alias, $enabledModules, true)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * @param  array<int, array<string, mixed>>  $apiIntegrations
     * @return array<int, array<string, mixed>>
     */
    private function buildRateLimitStatus(array $apiIntegrations): array
    {
        $rows = DB::table('api_rate_limit_tracking')
            ->select(['key', 'attempts', 'reset_at'])
            ->get();

        $status = [];

        foreach ($apiIntegrations as $api) {
            $prefix = is_string($api['rate_prefix']) ? $api['rate_prefix'] : '';
            $limit = is_numeric($api['limit']) ? (int) $api['limit'] : null;
            $used = 0;
            $keyCount = 0;
            $nextReset = null;

            foreach ($rows as $row) {
                $key = is_string($row->key ?? null) ? $row->key : '';
                $matches = str_ends_with($prefix, ':') ? str_starts_with($key, $prefix) : $key === $prefix;

                if (! $matches) {
                    continue;
                }

                $attempts = is_numeric($row->attempts ?? null) ? (int) $row->attempts : 0;
                $used += $attempts;
                $keyCount++;

                $rowReset = strtotime((string) ($row->reset_at ?? ''));
                if ($rowReset > time() && ($nextReset === null || $rowReset < $nextReset)) {
                    $nextReset = $rowReset;
                }
            }

            $usedPercent = ($limit !== null && $limit > 0) ? round(($used / $limit) * 100, 1) : null;
            $color = 'success';
            if ($usedPercent !== null) {
                if ($usedPercent >= 90) {
                    $color = 'danger';
                } elseif ($usedPercent >= 70) {
                    $color = 'warning';
                }
            }

            $status[] = [
                'api_key' => is_string($api['key']) ? $api['key'] : '',
                'name' => is_string($api['name']) ? $api['name'] : '',
                'limit' => $limit,
                'used' => $used,
                'remaining' => ($limit !== null && $limit > 0) ? max(0, $limit - $used) : null,
                'used_percent' => $usedPercent,
                'color' => $color,
                'key_count' => $keyCount,
                'reset_at' => $nextReset,
                'reset_in_seconds' => $nextReset !== null ? max(0, $nextReset - time()) : null,
                'reset_in_human' => $nextReset !== null
                    ? \Carbon\Carbon::createFromTimestamp($nextReset)->diffForHumans()
                    : 'N/A',
            ];
        }

        return $status;
    }

    private function probeAction1Summary(): JsonResponse
    {
        $roles = array_map(
            fn (Action1Role $role): array => $this->probeAction1Role($role),
            Action1Role::cases(),
        );
        $allConfigured = collect($roles)->every(fn (array $role): bool => (bool) ($role['ok'] ?? false));
        $latencyMs = array_sum(array_map(fn (array $role): int => is_numeric($role['latency_ms'] ?? null) ? (int) $role['latency_ms'] : 0, $roles));

        return response()->json([
            'ok' => $allConfigured,
            'api' => 'action1',
            'latency_ms' => $latencyMs,
            'message' => $allConfigured
                ? 'All 3 Action1 roles passed a canary probe. Run Deep Test for end-to-end validation.'
                : collect($roles)->where('ok', true)->count().'/'.count($roles).' Action1 roles passed canary probes.',
            'details' => ['roles' => $roles],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function probeAction1Role(Action1Role $role): array
    {
        $key = $role->configKey();
        if (empty(config("action1.roles.{$key}.client_id")) || empty(config("action1.roles.{$key}.client_secret"))) {
            return [
                'ok' => false,
                'role' => $role->value,
                'label' => $role->label(),
                'message' => 'Credentials not configured.',
                'latency_ms' => 0,
            ];
        }

        $start = microtime(true);

        try {
            cache()->forget($role->tokenCacheKey());

            if ($role === Action1Role::Sync) {
                $service = app(Action1SyncClient::class);
                $orgs = $service->listOrganizations();
                $message = 'Read-only org listing succeeded. Found '.count($orgs).' org(s).';
                $details = ['org_count' => count($orgs)];

                $testOrgId = config()->string('action1.test_probe.org_id', '');
                $testEpName = config()->string('action1.test_probe.endpoint_name', '');
                if ($testOrgId !== '' && $testEpName !== '') {
                    $endpoints = $service->listEndpoints($testOrgId);
                    $match = collect($endpoints)->first(fn (array $ep): bool => strcasecmp(is_string($ep['name'] ?? null) ? ($ep['name'] ?? '') : '', $testEpName) === 0);
                    if ($match !== null) {
                        $message .= " Test endpoint '{$testEpName}' resolved.";
                        $details['test_endpoint_name'] = $testEpName;
                    } else {
                        $message .= " Test endpoint '{$testEpName}' was not found in org {$testOrgId}.";
                    }
                }

                return [
                    'ok' => true,
                    'role' => $role->value,
                    'label' => $role->label(),
                    'message' => $message,
                    'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                    'details' => $details,
                ];
            }

            if ($role === Action1Role::ScriptManager) {
                $service = app(Action1ManageClient::class);
                $scripts = $service->listScripts();

                return [
                    'ok' => true,
                    'role' => $role->value,
                    'label' => $role->label(),
                    'message' => 'Read-only script listing succeeded. '.count($scripts).' script(s) visible.',
                    'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                    'details' => ['script_count' => count($scripts)],
                ];
            }

            $service = app(Action1RunClient::class);
            $testOrgId = config()->string('action1.test_probe.org_id', '');
            $enterpriseOrgId = config()->string('action1.enterprise_org_id', '');
            $orgId = $testOrgId !== '' ? $testOrgId : $enterpriseOrgId;

            if ($orgId === '') {
                $service->authenticate();

                return [
                    'ok' => true,
                    'role' => $role->value,
                    'label' => $role->label(),
                    'message' => 'OAuth2 authentication succeeded. Configure ACTION1_TEST_ORG_ID for a non-firing automation canary.',
                    'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                ];
            }

            $bogusAutomationId = '00000000-0000-0000-0000-000000000000';

            try {
                $status = $service->getAutomationStatus($orgId, $bogusAutomationId);

                return [
                    'ok' => true,
                    'role' => $role->value,
                    'label' => $role->label(),
                    'message' => 'Non-firing automation status canary succeeded.',
                    'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                    'details' => ['status' => $status['status'] ?? 'unknown'],
                ];
            } catch (\Throwable $e) {
                $message = $e->getMessage();
                $authRejected = str_contains(strtolower($message), 'unauthorized')
                    || str_contains(strtolower($message), 'forbidden')
                    || str_contains($message, '401')
                    || str_contains($message, '403');

                if (! $authRejected) {
                    return [
                        'ok' => true,
                        'role' => $role->value,
                        'label' => $role->label(),
                        'message' => 'Auth accepted; malformed non-firing automation canary returned a non-auth error: '.$message,
                        'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                    ];
                }

                throw $e;
            }
        } catch (\Throwable $e) {
            logger()->warning('Action1 health probe failed', ['role' => $role->value, 'error' => $e->getMessage()]);

            return [
                'ok' => false,
                'role' => $role->value,
                'label' => $role->label(),
                'message' => $e->getMessage(),
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            ];
        }
    }

    private function probeGoogleWorkspaceHomeDomain(): JsonResponse
    {
        $start = microtime(true);
        $credentialsPath = config()->string('google.credentials_path', storage_path('app/google-credentials.json'));
        $adminEmail = config()->string('google.admin_email', '');
        $domain = config()->string('google.domain', '');

        if ($credentialsPath === '' || ! is_file($credentialsPath)) {
            return response()->json([
                'ok' => false,
                'api' => 'google_workspace',
                'mode' => 'home_domain',
                'message' => 'GOOGLE_CREDENTIALS_PATH is missing or file does not exist.',
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            ]);
        }

        if ($adminEmail === '') {
            return response()->json([
                'ok' => false,
                'api' => 'google_workspace',
                'mode' => 'home_domain',
                'message' => 'GOOGLE_ADMIN_EMAIL is required for the home-domain probe.',
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            ]);
        }

        if ($domain === '') {
            return response()->json([
                'ok' => false,
                'api' => 'google_workspace',
                'mode' => 'home_domain',
                'message' => 'GOOGLE_DOMAIN must be configured for the home-domain probe.',
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            ]);
        }

        $result = $this->runGoogleWorkspaceConnectivityCheck(
            credentials: $credentialsPath,
            adminEmail: $adminEmail,
            domain: $domain,
            label: 'Home domain',
            target: $domain,
        );

        return response()->json([
            'ok' => $result['ok'],
            'api' => 'google_workspace',
            'mode' => 'home_domain',
            'message' => $result['ok']
                ? 'Home-domain Google Workspace probe passed.'
                : $result['message'],
            'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            'details' => [
                'home_domain' => $domain,
                'checks' => [$result],
            ],
        ], 200);
    }

    private function probeGoogleWorkspaceTenantSweep(): JsonResponse
    {
        $start = microtime(true);

        $configs = GoogleConfig::query()
            ->with('client:id,name')
            ->whereNotNull('domain')
            ->where('domain', '!=', '')
            ->orderBy('domain')
            ->get();

        if ($configs->isEmpty()) {
            return response()->json([
                'ok' => false,
                'api' => 'google_workspace',
                'mode' => 'tenant_sweep',
                'message' => 'No enrolled Google domains were found in GoogleAdmin settings.',
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                'details' => [
                    'checked_count' => 0,
                    'passed_count' => 0,
                    'failed_count' => 0,
                    'checks' => [],
                ],
            ], 200);
        }

        $checks = [];

        foreach ($configs as $config) {
            $clientName = is_object($config->client) ? (string) ($config->client->name ?? 'Unknown client') : 'Unknown client';
            $label = $clientName;
            $target = (string) $config->domain;

            if (! $config->hasCredentials()) {
                $checks[] = [
                    'ok' => false,
                    'label' => $label,
                    'target' => $target,
                    'message' => 'Credentials missing for enrolled domain.',
                    'latency_ms' => 0,
                    'client_id' => $config->client_id,
                    'client_name' => $clientName,
                    'domain' => $target,
                ];

                continue;
            }

            if (blank($config->admin_email)) {
                $checks[] = [
                    'ok' => false,
                    'label' => $label,
                    'target' => $target,
                    'message' => 'Admin email is missing for domain-wide delegation.',
                    'latency_ms' => 0,
                    'client_id' => $config->client_id,
                    'client_name' => $clientName,
                    'domain' => $target,
                ];

                continue;
            }

            $credentials = $config->getDecryptedCredentials();
            if (! is_array($credentials) || $credentials === []) {
                $checks[] = [
                    'ok' => false,
                    'label' => $label,
                    'target' => $target,
                    'message' => 'Stored credentials could not be decrypted or parsed.',
                    'latency_ms' => 0,
                    'client_id' => $config->client_id,
                    'client_name' => $clientName,
                    'domain' => $target,
                ];

                continue;
            }

            $checks[] = $this->runGoogleWorkspaceConnectivityCheck(
                credentials: $credentials,
                adminEmail: (string) $config->admin_email,
                domain: $target,
                label: $label,
                target: $target,
                clientId: (int) $config->client_id,
                clientName: $clientName,
                orgUnitPath: $config->org_unit_path,
            );
        }

        $passedCount = collect($checks)->where('ok', true)->count();
        $failedCount = count($checks) - $passedCount;

        return response()->json([
            'ok' => $failedCount === 0,
            'api' => 'google_workspace',
            'mode' => 'tenant_sweep',
            'message' => $failedCount === 0
                ? 'All enrolled Google Workspace domains passed connectivity tests.'
                : $passedCount.'/'.count($checks).' enrolled Google Workspace domains passed connectivity tests.',
            'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            'details' => [
                'checked_count' => count($checks),
                'passed_count' => $passedCount,
                'failed_count' => $failedCount,
                'checks' => $checks,
            ],
        ], 200);
    }

    /**
     * @param  string|array<mixed>  $credentials
     * @return array<string, mixed>
     */
    private function runGoogleWorkspaceConnectivityCheck(
        string|array $credentials,
        string $adminEmail,
        string $domain,
        string $label,
        string $target,
        ?int $clientId = null,
        ?string $clientName = null,
        ?string $orgUnitPath = null,
    ): array {
        $start = microtime(true);

        try {
            /** @var GoogleWorkspaceService $googleService */
            $googleService = app(GoogleWorkspaceService::class);
            $connected = $googleService->connect($credentials, $adminEmail);

            if (! $connected) {
                return [
                    'ok' => false,
                    'label' => $label,
                    'target' => $target,
                    'message' => 'Google client initialization failed with the configured credentials.',
                    'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                    'client_id' => $clientId,
                    'client_name' => $clientName,
                    'domain' => $domain,
                ];
            }

            $users = $googleService->listUsers($domain, $orgUnitPath);

            return [
                'ok' => true,
                'label' => $label,
                'target' => $target,
                'message' => 'Directory read succeeded. '.count($users).' user(s) visible in the sample query.',
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                'sample_user_count' => count($users),
                'client_id' => $clientId,
                'client_name' => $clientName,
                'domain' => $domain,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'label' => $label,
                'target' => $target,
                'message' => $e->getMessage(),
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                'client_id' => $clientId,
                'client_name' => $clientName,
                'domain' => $domain,
            ];
        }
    }

    private function probeHelcim(): JsonResponse
    {
        $start = microtime(true);

        try {
            $apiToken = config()->string('services.helcim.api_token', '');
            $apiUrl = rtrim(config()->string('services.helcim.api_url', 'https://api.helcim.com/v2'), '/');

            if ($apiToken === '') {
                return response()->json([
                    'ok' => false,
                    'api' => 'helcim',
                    'message' => 'HELCIM_API_TOKEN is not configured.',
                    'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                ]);
            }

            $response = Http::withHeaders([
                'api-token' => $apiToken,
                'Accept' => 'application/json',
            ])->timeout(config()->integer('services.helcim.timeout', 30))
                ->get("{$apiUrl}/customers", ['limit' => 1]);

            if (! $response->successful()) {
                return response()->json([
                    'ok' => false,
                    'api' => 'helcim',
                    'message' => 'Helcim responded with HTTP '.$response->status().'.',
                    'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                ]);
            }

            return response()->json([
                'ok' => true,
                'api' => 'helcim',
                'message' => 'Helcim API reachable. Read-only customer query succeeded.',
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'api' => 'helcim',
                'message' => $e->getMessage(),
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            ], 200);
        }
    }

    private function probeGemini(): JsonResponse
    {
        $start = microtime(true);

        try {
            /** @var GeminiClient $client */
            $client = app(GeminiClient::class);
            $result = $client->testConnection();

            return response()->json([
                'ok' => (bool) ($result['ok'] ?? false),
                'api' => 'gemini_api',
                'message' => (bool) ($result['ok'] ?? false)
                    ? 'Gemini API reachable. Models endpoint responded successfully.'
                    : ((string) ($result['error'] ?? 'Unknown Gemini error')),
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                'details' => [
                    'model_count' => $result['model_count'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'api' => 'gemini_api',
                'message' => $e->getMessage(),
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
            ], 200);
        }
    }
}
