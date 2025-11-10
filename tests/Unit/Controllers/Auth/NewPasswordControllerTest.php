<?php

namespace Tests\Unit\Controllers\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NewPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_reset_with_valid_token()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        
        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_password_reset_fails_with_invalid_token()
    {
        $user = User::factory()->create();
        
        $response = $this->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);
        
        $response->assertSessionHasErrors('email');
    }

    public function test_password_must_meet_requirements()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        
        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);
        
        $response->assertSessionHasErrors('password');
    }

    public function test_password_must_be_confirmed()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        
        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'DifferentPassword123!',
        ]);
        
        $response->assertSessionHasErrors('password');
    }

    public function test_password_is_hashed_after_reset()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $newPassword = 'NewPassword123!';
        
        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);
        
        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
    }
}
