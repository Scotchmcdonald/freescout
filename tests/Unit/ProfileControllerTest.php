<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected ProfileController $controller;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ProfileController;
        $this->user = User::factory()->create([
            'password' => Hash::make('current-password'),
        ]);
    }

    public function test_update_password_with_valid_data(): void
    {
        $this->actingAs($this->user);

        $request = Request::create('/profile/password', 'PUT', [
            'current_password' => 'current-password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->updatePassword($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->user->refresh();
        $this->assertTrue(Hash::check('new-password123', $this->user->password));
    }

    public function test_update_password_validates_current_password(): void
    {
        $this->actingAs($this->user);

        $request = Request::create('/profile/password', 'PUT', [
            'current_password' => 'wrong-password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);
        $request->setUserResolver(fn () => $this->user);

        try {
            $this->controller->updatePassword($request);
            $this->fail('Expected validation exception');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('current_password', $e->errors());
        }
    }

    public function test_update_password_requires_confirmation(): void
    {
        $this->actingAs($this->user);

        $request = Request::create('/profile/password', 'PUT', [
            'current_password' => 'current-password',
            'password' => 'new-password123',
            'password_confirmation' => 'different-password',
        ]);
        $request->setUserResolver(fn () => $this->user);

        try {
            $this->controller->updatePassword($request);
            $this->fail('Expected validation exception');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('password', $e->errors());
        }
    }

    public function test_update_password_enforces_password_rules(): void
    {
        $this->actingAs($this->user);

        $request = Request::create('/profile/password', 'PUT', [
            'current_password' => 'current-password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);
        $request->setUserResolver(fn () => $this->user);

        try {
            $this->controller->updatePassword($request);
            $this->fail('Expected validation exception');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('password', $e->errors());
        }
    }

    public function test_update_password_returns_with_status_message(): void
    {
        $this->actingAs($this->user);

        $request = Request::create('/profile/password', 'PUT', [
            'current_password' => 'current-password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);
        $request->setUserResolver(fn () => $this->user);

        $response = $this->controller->updatePassword($request);

        $this->assertEquals('password-updated', $response->getSession()->get('status'));
    }

    public function test_destroy_requires_password_confirmation(): void
    {
        $this->actingAs($this->user);

        $request = Request::create('/profile', 'DELETE', [
            'password' => 'wrong-password',
        ]);
        $request->setUserResolver(fn () => $this->user);

        try {
            $this->controller->destroy($request);
            $this->fail('Expected validation exception');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('password', $e->errors());
        }
    }

    public function test_destroy_deletes_user_and_logs_out(): void
    {
        $this->actingAs($this->user);

        $request = Request::create('/profile', 'DELETE', [
            'password' => 'current-password',
        ]);
        $request->setUserResolver(fn () => $this->user);
        $request->setLaravelSession($this->app['session.store']);

        $userId = $this->user->id;

        $response = $this->controller->destroy($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertNull(User::find($userId));
        $this->assertFalse(Auth::check());
    }
}
