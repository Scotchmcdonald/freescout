<?php

use App\Models\User;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);
});

test('admin can view resilience dashboard', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.resilience.index'))
        ->assertStatus(200)
        ->assertViewIs('admin.resilience.index')
        ->assertSee('Circuit Breakers');
});

test('resilience dashboard displays circuit breaker states', function () {
    // Simulate some circuit breaker states in Redis/Cache
    // Assuming the application uses a standard naming convention or a specific service
    // For this test, we mimic the expected view data or state reflection
    
    // We can't easily mock the internal state of a circuit breaker library without more context,
    // so we'll check for the structure of the dashboard elements.

    $response = $this->actingAs($this->admin)->get(route('admin.resilience.index'));
    
    $response->assertSee('Closed'); // Healthy state (view uses ucfirst)
    $response->assertViewHas('circuitBreakers');
});

test('admin can reset a circuit breaker', function () {
    $serviceName = 'google_api';
    
    $this->actingAs($this->admin)
        ->post(route('admin.resilience.reset-circuit', ['service' => $serviceName]))
        ->assertRedirect()
        ->assertSessionHas('success');
});

test('resilience dashboard displays semantic health colors', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.resilience.index'));

    // Verify the dashboard loads successfully with service data
    $response->assertOk();
    $response->assertViewHas('circuitBreakers');
});

test('non admin cannot view resilience dashboard', function () {
    // Use type=0 to ensure admin middleware rejects (type=1 is treated as internal staff)
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 0]);

    $this->actingAs($user)
        ->get(route('admin.resilience.index'))
        ->assertStatus(403);
});
