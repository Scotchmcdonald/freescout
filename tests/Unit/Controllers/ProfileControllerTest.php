<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_shows_profile_form()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('profile.edit'));
        $response->assertStatus(200);
        $response->assertViewIs('profile.edit');
    }

    public function test_update_modifies_profile_data()
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);
        $this->actingAs($user);

        $response = $this->patch(route('profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('new@example.com', $user->email);
    }

    public function test_update_validates_email_uniqueness()
    {
        User::factory()->create(['email' => 'existing@example.com']);
        $user = User::factory()->create(['email' => 'user@example.com']);
        $this->actingAs($user);

        $response = $this->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'existing@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_update_allows_current_user_email()
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $this->actingAs($user);

        $response = $this->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'user@example.com',
        ]);

        $response->assertSessionDoesntHaveErrors();
    }

    public function test_update_validates_name_required()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->patch(route('profile.update'), [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_update_preserves_unchanged_fields()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $this->actingAs($user);

        $this->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'test@example.com',
        ]);

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }

    public function test_destroy_deletes_user_account()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);
        $this->actingAs($user);

        $response = $this->delete(route('profile.destroy'), [
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
