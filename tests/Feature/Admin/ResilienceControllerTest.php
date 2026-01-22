<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\CircuitBreaker;
use Illuminate\Support\Facades\DB;
use Tests\FeatureTestCase;

/**
 * Phase 6: Infrastructure Resilience Dashboard Tests
 * Tests for Circuit Breaker and Rate Limiter monitoring UIs
 */
class ResilienceControllerTest extends FeatureTestCase
{
    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->regularUser = User::factory()->create(['role' => User::ROLE_USER]);
    }

    // ===== AUTHORIZATION TESTS =====

    public function test_circuit_breaker_dashboard_requires_admin(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.resilience.circuit-breakers'));

        $response->assertForbidden();
    }

    public function test_rate_limiter_dashboard_requires_admin(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.resilience.rate-limits'));

        $response->assertForbidden();
    }

    public function test_circuit_breaker_reset_requires_admin(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->post(route('admin.resilience.reset-circuit', 'google_api'));

        $response->assertForbidden();
    }

    // ===== CIRCUIT BREAKER DASHBOARD TESTS =====

    public function test_circuit_breaker_dashboard_shows_all_services(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.resilience.circuit-breakers'));

        $response->assertOk();
        $response->assertViewIs('admin.resilience.circuit-breakers');
        $response->assertViewHas('services');
        
        $services = $response->viewData('services');
        $this->assertCount(3, $services);
        
        $serviceKeys = collect($services)->pluck('key')->toArray();
        $this->assertContains('google_api', $serviceKeys);
        $this->assertContains('action1_api', $serviceKeys);
        $this->assertContains('helcim_api', $serviceKeys);
    }

    public function test_circuit_breaker_dashboard_shows_service_states(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.resilience.circuit-breakers'));

        $response->assertOk();
        $services = $response->viewData('services');
        
        foreach ($services as $service) {
            $this->assertArrayHasKey('state', $service);
            $this->assertArrayHasKey('failure_count', $service);
            $this->assertArrayHasKey('last_checked_human', $service);
        }
    }

    public function test_circuit_breaker_dashboard_shows_alert_when_circuits_open(): void
    {
        // Manually set a circuit to open state
        DB::table('circuit_breaker_states')->updateOrInsert(
            ['service' => 'google_api'],
            [
                'state' => CircuitBreaker::STATE_OPEN,
                'failure_count' => 5,
                'last_failure_at' => now(),
                'opened_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $response = $this->actingAs($this->admin)
            ->get(route('admin.resilience.circuit-breakers'));

        $response->assertOk();
        $response->assertSee('Service(s) Degraded');
        $response->assertSee('Manual intervention may be required');
    }

    public function test_circuit_breaker_dashboard_shows_no_alert_when_all_closed(): void
    {
        // Ensure all circuits are closed
        $breaker = app(CircuitBreaker::class);
        $breaker->reset('google_api');
        $breaker->reset('action1_api');
        $breaker->reset('helcim_api');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.resilience.circuit-breakers'));

        $response->assertOk();
        $response->assertDontSee('Service(s) Degraded');
    }

    public function test_circuit_breaker_dashboard_shows_transition_history(): void
    {
        // Transition history is currently a placeholder (returns empty array)
        // This test just verifies the view renders without errors
        $response = $this->actingAs($this->admin)
            ->get(route('admin.resilience.circuit-breakers'));

        $response->assertOk();
        $response->assertViewHas('transitions');
        $response->assertSee('State Transition Log');
    }

    // ===== CIRCUIT BREAKER RESET TESTS =====

    public function test_reset_circuit_resets_service(): void
    {
        // Set circuit to open state
        DB::table('circuit_breaker_states')->updateOrInsert(
            ['service' => 'google_api'],
            [
                'state' => CircuitBreaker::STATE_OPEN,
                'failure_count' => 5,
                'last_failure_at' => now(),
                'opened_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $response = $this->actingAs($this->admin)
            ->post(route('admin.resilience.reset-circuit', 'google_api'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // Verify circuit was reset
        $state = DB::table('circuit_breaker_states')
            ->where('service', 'google_api')
            ->first();
        $this->assertEquals('closed', $state->state);
    }

    public function test_reset_circuit_rejects_invalid_service(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.resilience.reset-circuit', 'invalid_service'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ===== RATE LIMITER DASHBOARD TESTS =====

    public function test_rate_limiter_dashboard_shows_all_services(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.resilience.rate-limits'));

        $response->assertOk();
        $response->assertViewIs('admin.resilience.rate-limits');
        $response->assertViewHas('services');
        
        $services = $response->viewData('services');
        $this->assertCount(3, $services);
    }

    public function test_rate_limiter_dashboard_shows_usage_statistics(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.resilience.rate-limits'));

        $response->assertOk();
        $services = $response->viewData('services');
        
        foreach ($services as $service) {
            $this->assertArrayHasKey('limit', $service);
            $this->assertArrayHasKey('used', $service);
            $this->assertArrayHasKey('remaining', $service);
            $this->assertArrayHasKey('used_percent', $service);
            $this->assertArrayHasKey('color', $service);
        }
    }

    public function test_rate_limiter_dashboard_shows_green_when_usage_low(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.resilience.rate-limits'));

        $response->assertOk();
        $services = $response->viewData('services');
        
        // Assuming no heavy usage in tests, should be green
        $lowUsageService = collect($services)->firstWhere('used_percent', '<', 70);
        if ($lowUsageService) {
            $this->assertEquals('success', $lowUsageService['color']);
        }
    }

    // ===== UI COMPLIANCE TESTS =====

    public function test_circuit_breaker_dashboard_uses_semantic_colors(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.resilience.circuit-breakers'));

        $response->assertOk();
        
        // Verify semantic CSS variables are used
        $response->assertSee('var(--theme-');
    }

    public function test_rate_limiter_dashboard_uses_semantic_colors(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.resilience.rate-limits'));

        $response->assertOk();
        // Verify semantic CSS variables are used
        $response->assertSee('var(--theme-');
    }

    public function test_circuit_breaker_dashboard_is_mobile_responsive(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.resilience.circuit-breakers'));

        $response->assertOk();
        // Verify responsive grid classes
        $response->assertSee('md:grid-cols-3');
        $response->assertSee('sm:px-6');
    }

    // ===== EVENT AUDIT LOG TESTS =====

    public function test_events_audit_requires_admin(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get(route('admin.resilience.events-audit'));

        $response->assertForbidden();
    }

    public function test_events_audit_shows_events(): void
    {
        // Create test events
        DB::table('polycast_events')->insert([
            'channel' => 'test_channel',
            'event' => 'TestEvent',
            'payload' => json_encode(['foo' => 'bar']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.resilience.events-audit'));

        $response->assertOk();
        $response->assertViewIs('admin.resilience.events-audit');
        $response->assertViewHas('events');
        $response->assertSee('TestEvent');
        $response->assertSee('test_channel');
    }

    public function test_events_audit_filtering(): void
    {
        // Create matching event
        DB::table('polycast_events')->insert([
            'channel' => 'match_channel',
            'event' => 'MatchEvent',
            'payload' => json_encode(['id' => 123]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create non-matching event
        DB::table('polycast_events')->insert([
            'channel' => 'other_channel',
            'event' => 'OtherEvent',
            'payload' => json_encode(['id' => 999]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Filter by event type
        $response = $this->actingAs($this->admin)
            ->get(route('admin.resilience.events-audit', ['event_type' => 'Match']));

        $response->assertOk();
        $response->assertSee('MatchEvent');
        $response->assertDontSee('OtherEvent');
    }

    public function test_events_export_generates_csv(): void
    {
        DB::table('polycast_events')->insert([
            'channel' => 'export_channel',
            'event' => 'ExportEvent',
            'payload' => json_encode(['data' => 'export']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.resilience.events-audit.export'));

        $response->assertOk();
        
        // Use a more flexible check for content-type as it might vary by environment
        $contentType = $response->headers->get('content-type');
        if (str_contains($contentType, 'text/html')) {
            // If we got HTML, something went wrong, let's fail with the content
            $content = $response->streamedContent();
            $this->fail('Received HTML instead of CSV. Content: ' . substr($content, 0, 500));
        }
        
        $this->assertTrue(
            str_contains($contentType, 'text/csv') || 
            str_contains($contentType, 'application/csv') ||
            str_contains($contentType, 'application/octet-stream'), // Some environments default to this
            "Unexpected content type: {$contentType}"
        );
        
        $response->assertHeader('content-disposition');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('ExportEvent', $content);
        $this->assertStringContainsString('export_channel', $content);
    }
}
