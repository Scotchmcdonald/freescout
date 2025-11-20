<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Models\User;
use Tests\IntegrationTestCase;

class WebhookControllerTest extends IntegrationTestCase
{
    public function test_index_displays_webhooks_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get('/webhooks');

        $response->assertStatus(200);
        $response->assertViewIs('webhooks.index');
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->get('/webhooks');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_create_displays_create_form(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get('/webhooks/create');

        $response->assertStatus(200);
        $response->assertViewIs('webhooks.create');
    }

    public function test_create_requires_authentication(): void
    {
        $response = $this->get('/webhooks/create');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_store_validates_url_is_required(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post('/webhooks', [
            'events' => ['conversation.created'],
        ]);

        $response->assertSessionHasErrors('url');
    }

    public function test_store_validates_url_format(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post('/webhooks', [
            'url' => 'not-a-valid-url',
            'events' => ['conversation.created'],
        ]);

        $response->assertSessionHasErrors('url');
    }

    public function test_store_validates_events_is_required(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post('/webhooks', [
            'url' => 'https://example.com/webhook',
        ]);

        $response->assertSessionHasErrors('events');
    }

    public function test_store_validates_events_is_array(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post('/webhooks', [
            'url' => 'https://example.com/webhook',
            'events' => 'not-an-array',
        ]);

        $response->assertSessionHasErrors('events');
    }

    public function test_store_redirects_to_webhooks_index_on_success(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post('/webhooks', [
            'url' => 'https://example.com/webhook',
            'events' => ['conversation.created', 'conversation.updated'],
        ]);

        $response->assertRedirect(route('webhooks'));
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->post('/webhooks', [
            'url' => 'https://example.com/webhook',
            'events' => ['conversation.created'],
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_store_accepts_valid_webhook_data(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post('/webhooks', [
            'url' => 'https://example.com/webhook',
            'events' => ['conversation.created', 'conversation.updated'],
        ]);

        $response->assertSessionDoesntHaveErrors();
    }
}
