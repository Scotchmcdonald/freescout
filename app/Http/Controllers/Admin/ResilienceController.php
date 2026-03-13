<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CircuitBreakerService;
use App\Services\RateLimiterService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Admin controller for Infrastructure Resilience monitoring.
 * Phase 6: Read-only dashboards for Circuit Breaker and Rate Limiter services.
 */
class ResilienceController extends Controller
{
    /**
     * Combined Resilience Dashboard - Circuit Breakers & Rate Limiters
     */
    public function index(): View
    {
        // --- Circuit Breaker Logic ---
        $breaker = app(CircuitBreakerService::class);
        $cbServices = ['google_api', 'action1_api', 'helcim_api', 'gemini_api'];
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
        $rateLimiter = app(RateLimiterService::class);

        // Define service rate limits
        $rateLimitServicesConfig = [
            [
                'name' => 'Google Workspace API',
                'key' => 'google_api_hourly',
                'limit' => 10000,
            ],
            [
                'name' => 'Action1 API',
                'key' => 'action1_api_hourly',
                'limit' => 5000,
            ],
            [
                'name' => 'Helcim Payment API',
                'key' => 'helcim_api_hourly',
                'limit' => 1000,
            ],
            [
                'name' => 'Gemini AI API',
                'key' => 'gemini_api_hourly',
                'limit' => 1500,
            ],
        ];

        $rateLimitStatus = $rateLimiter->getUsageStats($rateLimitServicesConfig);

        return view('admin.resilience.index', [
            'circuitBreakers' => $circuitBreakerStatus,
            'openCircuits' => $openCircuits,
            'rateLimits' => $rateLimitStatus,
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
        $query = DB::table('polycast_events');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                    ->orWhere('payload', 'like', "%{$search}%")
                    ->orWhere('channel', 'like', "%{$search}%");
            });
        }

        if ($request->filled('event_type')) {
            $eventType = $request->string('event_type')->toString();
            $query->where('event', 'like', "%{$eventType}%");
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->string('date_to')->toString().' 23:59:59');
        }

        $events = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.resilience.events-audit', [
            'events' => $events,
            'filters' => $request->only(['search', 'event_type', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Export events to CSV.
     */
    public function exportEvents(\Illuminate\Http\Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($request) {
            $query = DB::table('polycast_events');

            if ($request->filled('search')) {
                $search = $request->string('search')->toString();
                $query->where(function ($q) use ($search) {
                    $q->where('event', 'like', "%{$search}%")
                        ->orWhere('payload', 'like', "%{$search}%")
                        ->orWhere('channel', 'like', "%{$search}%");
                });
            }

            if ($request->filled('event_type')) {
                $eventType = $request->string('event_type')->toString();
                $query->where('event', 'like', "%{$eventType}%");
            }

            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->input('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->string('date_to')->toString().' 23:59:59');
            }

            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fputcsv($handle, ['ID', 'Channel', 'Event', 'Payload', 'Timestamp']);

            $query->orderBy('created_at', 'desc')->chunk(1000, function ($events) use ($handle) {
                foreach ($events as $event) {
                    fputcsv($handle, [
                        $event->id,
                        $event->channel,
                        $event->event,
                        $event->payload,
                        $event->created_at,
                    ]);
                }
            });

            fclose($handle);
        }, 'events-audit-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv']);
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
            'google_api' => 'Google Workspace API',
            'action1_api' => 'Action1 RMM API',
            'helcim_api' => 'Helcim Payment Gateway',
            'gemini_api' => 'Gemini AI API',
            default => ucwords(str_replace('_', ' ', $service)),
        };
    }
}
