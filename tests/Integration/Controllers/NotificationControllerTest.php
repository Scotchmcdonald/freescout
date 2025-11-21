<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Models\User;
use Tests\IntegrationTestCase;

class NotificationControllerTest extends IntegrationTestCase
{
    public function test_index_displays_notifications_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertStatus(200);
        $response->assertViewIs('notifications.index');
    }

    public function test_index_passes_notifications_to_view(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertViewHas('notifications');
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->get('/notifications');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_mark_as_read_redirects_back(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/notifications')
            ->post('/notifications/1/read');

        $response->assertRedirect('/notifications');
    }

    public function test_mark_as_read_requires_authentication(): void
    {
        $response = $this->post('/notifications/1/read');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_mark_as_read_accepts_notification_id(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/notifications/123/read');

        $response->assertStatus(302);
    }
}
