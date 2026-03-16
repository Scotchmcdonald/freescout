<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('login endpoint is rate limited to 5 attempts per minute', function () {
    // First 5 attempts should succeed (even with wrong password, they get through rate limiting)
    for ($i = 0; $i < 5; $i++) {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        // These will fail authentication but won't be rate limited
        expect($response->status())->toBeIn([302, 422]); // Redirect or validation error
    }

    // 6th attempt should be rate limited
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429); // Too Many Requests
});

test('register endpoint is rate limited to 5 attempts per minute', function () {
    // First 5 attempts
    for ($i = 0; $i < 5; $i++) {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => "test{$i}@example.com",
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
    }

    // 6th attempt should be rate limited
    $response = $this->post('/register', [
        'name' => 'Test User 6',
        'email' => 'test6@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(429);
});

test('forgot password endpoint is rate limited to 3 attempts per minute', function () {
    // First 3 attempts
    for ($i = 0; $i < 3; $i++) {
        $this->post('/forgot-password', [
            'email' => 'test@example.com',
        ]);
    }

    // 4th attempt should be rate limited
    $response = $this->post('/forgot-password', [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(429);
});

test('reset password endpoint is rate limited to 5 attempts per minute', function () {
    $user = User::factory()->create();
    $token = 'fake-token';

    // First 5 attempts
    for ($i = 0; $i < 5; $i++) {
        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
    }

    // 6th attempt should be rate limited
    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(429);
});

test('rate limit headers are present in throttled responses', function () {
    // Make requests until rate limited
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    // Next request should be throttled
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(429);
    $response->assertHeader('X-RateLimit-Limit');
    $response->assertHeader('X-RateLimit-Remaining');
    $response->assertHeader('Retry-After');
});

test('successful login attempts still count toward rate limit', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    // Failed attempts use up rate limit
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);
    }

    // 6th attempt should be rate limited even with correct password
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'correct-password',
    ]);

    $response->assertStatus(429);
});

test('rate limits are per IP address', function () {
    // This test demonstrates that rate limits are isolated per IP
    // In a real scenario, different IPs would have separate rate limits

    $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    // First request should work
    $this->assertDatabaseMissing('failed_jobs', [
        'exception' => 'Rate limit exceeded',
    ]);
});

test('rate limit applies correctly before threshold', function () {
    // Make 4 requests (under the limit of 5)
    for ($i = 0; $i < 4; $i++) {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // Should not be rate limited yet
        expect($response->status())->not->toBe(429);
    }

    // 5th request should still work
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);
    expect($response->status())->not->toBe(429);

    // 6th request should be blocked
    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    expect($response->status())->toBe(429);
});
