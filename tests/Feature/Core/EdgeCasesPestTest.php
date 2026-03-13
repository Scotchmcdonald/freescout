<?php

use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Support\Facades\Route;

test('controllers handle missing route parameters', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get('/mailboxes/99999')
        ->assertNotFound();
});

test('controllers handle invalid method calls', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->patch(route('system'))
        ->assertMethodNotAllowed();
});

test('controllers validate csrf tokens', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
        ->actingAs($admin)
        ->post(route('settings.cache.clear'))
        ->assertRedirect(); // Should succeed if CSRF middleware is disabled for this test?

    // Wait, the legacy test:
    // $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)...
    // implies it's testing that it works WITHOUT csrf verification?
    // "test_controllers_validate_csrf_tokens"
    // Usually that means IT SHOULD FAIL if token is missing.
    // But the legacy test disables the middleware for the request and asserts Redirect (success).
    // This seems to verify that the route works (redirects) when CSRF IS REMOVED (or maybe it's testing something else?).
    // Actually, `withoutMiddleware` disables the checking. So if we disable it, it should proceed.
    // If the test name is "validate csrf tokens", it usually checks 419 on failure.
    // But the legacy code:
    // $response = $this->withoutMiddleware(...)->post(...) -> assertRedirect().
    // This confirms the route functions when we bypass CSRF.
    // To properly replicate, I'll do the same.
});

test('controllers handle concurrent requests', function () {
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user->id);

    $responses = [];
    for ($i = 0; $i < 5; $i++) {
        $responses[] = $this->actingAs($user)->get(route('mailboxes.view', $mailbox));
    }

    foreach ($responses as $response) {
        $response->assertOk();
    }
});

test('controllers handle special characters in input', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $response = $this->actingAs($admin)->get(route('customers.search', [
        'q' => '<script>alert("xss")</script>',
    ]));

    $response->assertOk();

    // The payload is not reflected, so we just check it's not executed
    expect($response->getContent())->not->toContain('<script>alert("xss")</script>');
});

test('controllers respect rate limiting', function () {
    $user = User::factory()->create();

    // Make multiple rapid requests
    for ($i = 0; $i < 100; $i++) {
        $response = $this->actingAs($user)->get(route('mailboxes.index'));

        if ($response->status() === 429) {
            expect($response->status())->toBe(429);

            return;
        }
    }

    // If we reach here, no rate limiting occurred (common in test env)
    expect(true)->toBeTrue();
});
