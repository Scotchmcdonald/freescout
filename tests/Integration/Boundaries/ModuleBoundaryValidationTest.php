<?php

declare(strict_types=1);

/**
 * Module Boundary Validation Tests
 *
 * Consolidated boundary tests ensuring validation, authorization, and throttle
 * protections are enforced across module contracts and API boundaries.
 *
 * Coverage areas:
 * - Authorization guards on module service entry points
 * - Validation of input at service boundaries
 * - Rate limiter and throttle enforcement
 * - 403/422/429 response contracts
 */

use App\Models\User;
use App\Services\RateLimiterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

// ─── Authorization Boundary Tests ────────────────────────────────────────────

test('unauthenticated requests to protected module routes return 403 or redirect', function () {
    // Module routes require authentication — unauthorized access must be denied
    $protectedRoutes = [
        ['GET', '/settings/general'],
        ['GET', '/mailboxes'],
    ];

    foreach ($protectedRoutes as [$method, $uri]) {
        $response = $this->call($method, $uri);

        // Must redirect to login (302) or return 403 — never 200
        expect($response->status())
            ->toBeIn([302, 401, 403],
                "Route {$method} {$uri} returned {$response->status()} without authentication");
    }
});

test('non-admin users receive 403 on admin-only module settings', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 0]);

    $adminRoutes = [
        ['GET', '/settings/general'],
    ];

    foreach ($adminRoutes as [$method, $uri]) {
        $response = $this->actingAs($user)->call($method, $uri);

        // Must be 403 or redirect — never 200
        expect($response->status())
            ->toBeIn([302, 403],
                "Admin route {$method} {$uri} returned {$response->status()} for non-admin user");
    }
});

test('authorization boundary enforces role-based access control on settings', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 0]);

    // Admin should succeed (200 or redirect to settings page)
    $adminResponse = $this->actingAs($admin)->get('/settings/general');
    expect($adminResponse->status())->toBeIn([200, 302]);

    // Regular user should be denied (403 or redirect away)
    $userResponse = $this->actingAs($user)->get('/settings/general');
    expect($userResponse->status())->toBeIn([302, 403]);
});

// ─── Validation Boundary Tests ───────────────────────────────────────────────

test('validation rejects empty required fields on user creation', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->postJson('/users/ajax', [
        'action' => 'create',
        // Missing required fields: first_name, email
    ]);

    // Must return validation error (422) or error response — never 200 with silent failure
    $isValidationError = $response->status() === 422
        || ($response->json('status') === 'error')
        || $response->json('success') === false;

    expect($isValidationError)->toBeTrue(
        'User creation without required fields must trigger validation error'
    );
});

test('validation rejects malformed email addresses at service boundary', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->postJson('/users/ajax', [
        'action' => 'create',
        'first_name' => 'Test',
        'email' => 'not-an-email',
    ]);

    // Must reject invalid email — validation boundary must catch this
    $isRejected = $response->status() === 422
        || $response->json('success') === false
        || $response->json('status') === 'error';

    expect($isRejected)->toBeTrue(
        'Malformed email must be caught by validation at the boundary'
    );
});

test('validation enforces maximum length constraints on user input', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->postJson('/users/ajax', [
        'action' => 'create',
        'first_name' => str_repeat('A', 300), // Exceeds typical VARCHAR(255)
        'email' => 'valid@example.com',
    ]);

    // Must either reject (422) or truncate — boundary must not crash
    expect($response->status())->not->toBe(500,
        'Oversized input must be handled gracefully at the validation boundary'
    );
});

// ─── Rate Limiter / Throttle Boundary Tests ──────────────────────────────────

test('RateLimiterService enforces throttle limits on repeated operations', function () {
    if (! class_exists(RateLimiterService::class)) {
        $this->markTestSkipped('RateLimiterService not available');
    }

    $limiter = app(RateLimiterService::class);
    $key = 'test:rate_limit:' . uniqid();
    $maxAttempts = 3;
    $callback = fn () => true;

    // First N attempts should pass (callback returns its result)
    for ($i = 0; $i < $maxAttempts; $i++) {
        $result = $limiter->attempt($key, $maxAttempts, 60, $callback);
        expect($result)->toBeTrue("Attempt {$i} should be allowed under the rate limit");
    }

    // Next attempt should throw ThrottleRequestsException
    $throttled = false;
    try {
        $limiter->attempt($key, $maxAttempts, 60, $callback);
    } catch (\Illuminate\Http\Exceptions\ThrottleRequestsException) {
        $throttled = true;
    }

    expect($throttled)->toBeTrue('Attempt beyond the rate limit threshold must be throttled');
});

test('login throttle returns 429 after exceeding attempt threshold', function () {
    Cache::flush();

    // Make multiple failed login attempts to trigger rate limiter
    for ($i = 0; $i < 6; $i++) {
        $this->post('/login', [
            'email' => 'throttle-test@example.com',
            'password' => 'wrong-password-' . $i,
        ]);
    }

    $response = $this->post('/login', [
        'email' => 'throttle-test@example.com',
        'password' => 'still-wrong',
    ]);

    // Must return 429 (Too Many Requests) or show throttle message
    $isThrottled = $response->status() === 429
        || str_contains((string) $response->getContent(), 'throttle')
        || str_contains((string) $response->getContent(), 'Too many')
        || str_contains((string) $response->getContent(), 'too many');

    expect($isThrottled)->toBeTrue(
        'Login endpoint must enforce rate limiting after excessive failed attempts'
    );
});

// ─── Cross-Module Authorization Boundary Tests ──────────────────────────────

test('authorization prevents cross-mailbox conversation access', function () {
    $user1 = User::factory()->create(['role' => User::ROLE_USER, 'type' => 0]);
    $user2 = User::factory()->create(['role' => User::ROLE_USER, 'type' => 0]);

    $mailbox1 = \App\Models\Mailbox::factory()->create();
    $mailbox2 = \App\Models\Mailbox::factory()->create();

    // Attach user1 to mailbox1 only
    $mailbox1->users()->attach($user1->id);
    // Attach user2 to mailbox2 only
    $mailbox2->users()->attach($user2->id);

    $folder = \App\Models\Folder::factory()->create([
        'mailbox_id' => $mailbox2->id,
        'type' => \App\Models\Folder::TYPE_INBOX,
    ]);

    $customer = \App\Models\Customer::factory()->create();
    \App\Models\Email::factory()->create([
        'customer_id' => $customer->id,
        'email' => 'boundary-test@example.com',
    ]);

    $conversation = \App\Models\Conversation::factory()->for($mailbox2)->create([
        'customer_id' => $customer->id,
        'folder_id' => $folder->id,
    ]);

    // User1 trying to access mailbox2's conversation → must be denied
    $response = $this->actingAs($user1)->get("/conversation/{$conversation->id}");

    $isDenied = $response->status() === 403
        || $response->status() === 302; // Redirect away

    expect($isDenied)->toBeTrue(
        'Cross-mailbox authorization boundary must prevent unauthorized conversation access'
    );
});

test('authorization boundary validates mailbox ownership before operations', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 0]);

    $mailbox = \App\Models\Mailbox::factory()->create();
    // Admin is attached, user is NOT
    $mailbox->users()->attach($admin->id);

    $folder = \App\Models\Folder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'type' => \App\Models\Folder::TYPE_INBOX,
    ]);

    $customer = \App\Models\Customer::factory()->create();
    \App\Models\Email::factory()->create([
        'customer_id' => $customer->id,
        'email' => 'boundary-owner@example.com',
    ]);

    $conversation = \App\Models\Conversation::factory()->for($mailbox)->create([
        'customer_id' => $customer->id,
        'folder_id' => $folder->id,
    ]);

    // Non-attached user should be blocked from conversation operations
    $response = $this->actingAs($user)->postJson(route('conversations.ajax'), [
        'action' => 'follow',
        'conversation_id' => $conversation->id,
    ]);

    $isDenied = $response->status() === 403
        || $response->json('success') === false;

    expect($isDenied)->toBeTrue(
        'Authorization boundary must validate mailbox ownership before conversation operations'
    );
});
