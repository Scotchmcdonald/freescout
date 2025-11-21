<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Models\User;
use Tests\IntegrationTestCase;

class LocaleControllerTest extends IntegrationTestCase
{
    public function test_update_changes_user_locale(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $response = $this->actingAs($user)->post('/locale', [
            'locale' => 'es',
        ]);

        $response->assertRedirect();
        $this->assertEquals('es', $user->fresh()->locale);
    }

    public function test_update_validates_locale_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/locale', []);

        $response->assertSessionHasErrors('locale');
    }

    public function test_update_validates_locale_is_string(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/locale', [
            'locale' => 123,
        ]);

        $response->assertSessionHasErrors('locale');
    }

    public function test_update_validates_locale_max_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/locale', [
            'locale' => 'toolong',
        ]);

        $response->assertSessionHasErrors('locale');
    }

    public function test_update_returns_redirect_back(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/settings')
            ->post('/locale', [
                'locale' => 'fr',
            ]);

        $response->assertRedirect('/settings');
    }

    public function test_update_requires_authentication(): void
    {
        $response = $this->post('/locale', [
            'locale' => 'es',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_update_saves_locale_to_database(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)->post('/locale', [
            'locale' => 'de',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale' => 'de',
        ]);
    }
}
