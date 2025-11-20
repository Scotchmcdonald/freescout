<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Models\User;
use Tests\IntegrationTestCase;

class TagControllerTest extends IntegrationTestCase
{
    public function test_ajax_search_returns_json_response(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/tags/ajax-search');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_ajax_search_returns_empty_array(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/tags/ajax-search');

        $response->assertJson([]);
    }

    public function test_ajax_search_accepts_query_parameter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/tags/ajax-search?q=test');

        $response->assertStatus(200);
        $response->assertJson([]);
    }

    public function test_ajax_search_requires_authentication(): void
    {
        $response = $this->get('/tags/ajax-search');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
}
