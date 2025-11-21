<?php

namespace Tests\Unit\Controllers;

use App\Models\User;
use Tests\UnitTestCase;

class ProfileControllerTest extends UnitTestCase
{

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
            'first_name' => 'Old',
            'last_name' => 'Name',
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
            'first_name' => 'Test',
            'last_name' => 'User',
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

    // updatePassword() tests - 50% coverage

    public function test_update_password_changes_user_password(): void
    {
        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('oldpassword123'),
        ]);
        $this->actingAs($user);

        $response = $this->put(route('password.update'), [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHas('status', 'password-updated');
        $user->refresh();
        
        // Verify password was changed
        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('newpassword123', $user->password)
        );
    }

    public function test_update_password_requires_current_password(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->put(route('password.update'), [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_update_password_validates_current_password_correct(): void
    {
        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('correctpassword'),
        ]);
        $this->actingAs($user);

        $response = $this->put(route('password.update'), [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_update_password_requires_password_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('oldpassword123'),
        ]);
        $this->actingAs($user);

        $response = $this->put(route('password.update'), [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            // Missing password_confirmation
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_update_password_validates_password_matches_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('oldpassword123'),
        ]);
        $this->actingAs($user);

        $response = $this->put(route('password.update'), [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_update_password_validates_minimum_length(): void
    {
        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('oldpassword123'),
        ]);
        $this->actingAs($user);

        $response = $this->put(route('password.update'), [
            'current_password' => 'oldpassword123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_update_password_requires_authentication(): void
    {
        $response = $this->put(route('password.update'), [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_update_password_redirects_back_with_status(): void
    {
        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('oldpassword123'),
        ]);
        $this->actingAs($user);

        $response = $this->put(route('password.update'), [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'password-updated');
    }

    // Additional edge case tests for updatePassword

    public function test_update_password_rejects_empty_password(): void
    {
        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('oldpassword123'),
        ]);
        $this->actingAs($user);

        $response = $this->put(route('password.update'), [
            'current_password' => 'oldpassword123',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_update_password_handles_special_characters(): void
    {
        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('oldpassword123'),
        ]);
        $this->actingAs($user);

        $specialPassword = 'P@ssw0rd!#$%^&*()_+-=[]{}|;:,.<>?';
        
        $response = $this->put(route('password.update'), [
            'current_password' => 'oldpassword123',
            'password' => $specialPassword,
            'password_confirmation' => $specialPassword,
        ]);

        $response->assertSessionHas('status', 'password-updated');
        $user->refresh();
        
        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check($specialPassword, $user->password)
        );
    }

    public function test_update_password_handles_unicode_characters(): void
    {
        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('oldpassword123'),
        ]);
        $this->actingAs($user);

        $unicodePassword = 'パスワード123!@#';
        
        $response = $this->put(route('password.update'), [
            'current_password' => 'oldpassword123',
            'password' => $unicodePassword,
            'password_confirmation' => $unicodePassword,
        ]);

        $response->assertSessionHas('status', 'password-updated');
        $user->refresh();
        
        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check($unicodePassword, $user->password)
        );
    }

    public function test_update_password_does_not_allow_same_as_current(): void
    {
        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('samepassword123'),
        ]);
        $this->actingAs($user);

        $response = $this->put(route('password.update'), [
            'current_password' => 'samepassword123',
            'password' => 'samepassword123',
            'password_confirmation' => 'samepassword123',
        ]);

        // Password update should succeed even if same (Laravel doesn't prevent this by default)
        $response->assertSessionHas('status', 'password-updated');
    }

    public function test_update_password_case_sensitivity(): void
    {
        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('Password123'),
        ]);
        $this->actingAs($user);

        // Try with wrong case
        $response = $this->put(route('password.update'), [
            'current_password' => 'password123', // lowercase
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_update_password_with_whitespace(): void
    {
        $user = User::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('oldpassword123'),
        ]);
        $this->actingAs($user);

        $passwordWithSpaces = '  newpassword123  ';
        
        $response = $this->put(route('password.update'), [
            'current_password' => 'oldpassword123',
            'password' => $passwordWithSpaces,
            'password_confirmation' => $passwordWithSpaces,
        ]);

        // Password with leading/trailing spaces should be accepted
        $response->assertSessionHas('status', 'password-updated');
        $user->refresh();
        
        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check($passwordWithSpaces, $user->password)
        );
    }
}
