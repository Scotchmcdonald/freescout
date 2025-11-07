<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
        $response->assertSee('Register');
        $response->assertSee('Name');
        $response->assertSee('Email');
        $response->assertSee('Password');
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        
        // Verify user is authenticated
        $this->assertAuthenticated();
        
        // Verify user exists in database with correct data
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);
        
        // Verify user attributes
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(\Hash::check('password', $user->password));
    }
}
