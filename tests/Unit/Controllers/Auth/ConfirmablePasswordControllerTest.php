<?php

namespace Tests\Unit\Controllers\Auth;

use App\Models\User;
use Tests\UnitTestCase;

class ConfirmablePasswordControllerTest extends UnitTestCase
{

    public function test_show_displays_confirmation_form()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('password.confirm'));
        $response->assertStatus(200);
        $response->assertViewIs('auth.confirm-password');
    }

    public function test_store_confirms_with_correct_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);
        $this->actingAs($user);

        $response = $this->post(route('password.confirm'), [
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_store_fails_with_incorrect_password()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);
        $this->actingAs($user);

        $response = $this->post(route('password.confirm'), [
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_store_validates_password_required()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('password.confirm'), []);

        $response->assertSessionHasErrors('password');
    }

    public function test_store_sets_confirmation_timestamp()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);
        $this->actingAs($user);

        $this->post(route('password.confirm'), [
            'password' => 'password123',
        ]);

        $this->assertNotNull(session('auth.password_confirmed_at'));
    }

    public function test_guest_cannot_confirm_password()
    {
        $response = $this->get(route('password.confirm'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_submit_password_confirmation()
    {
        $response = $this->post(route('password.confirm'), [
            'password' => 'password123',
        ]);
        $response->assertRedirect(route('login'));
    }

    public function test_confirmation_persists_in_session()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);
        $this->actingAs($user);

        $this->post(route('password.confirm'), [
            'password' => 'password123',
        ]);

        $confirmationTime = session('auth.password_confirmed_at');
        $this->assertIsInt($confirmationTime);
        $this->assertGreaterThan(time() - 60, $confirmationTime);
    }
}
