<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\FeatureTestCase;

/**
 * Feature tests for UserController AJAX methods added during Phase 5 implementation.
 */
class UserControllerAjaxTest extends FeatureTestCase
{
    protected User $admin;
    protected User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->targetUser = User::factory()->create([
            'role' => User::ROLE_USER,
            'invite_hash' => 'test-hash-' . uniqid(),
            'invite_state' => User::INVITE_STATE_SENT,
        ]);
    }

    // ===== ajaxDeletePhoto tests =====

    public function test_admin_can_delete_user_photo(): void
    {
        $this->actingAs($this->admin);
        Storage::fake('public');

        // Simulate user has a photo
        $this->targetUser->photo_url = 'photos/test.jpg';
        $this->targetUser->save();

        $response = $this->postJson(route('users.ajax'), [
            'action' => 'delete_photo',
            'user_id' => $this->targetUser->id,
        ]);

        $response->assertOk();
    }

    public function test_user_can_delete_own_photo(): void
    {
        $this->actingAs($this->targetUser);
        Storage::fake('public');

        $this->targetUser->photo_url = 'photos/test.jpg';
        $this->targetUser->save();

        $response = $this->postJson(route('users.ajax'), [
            'action' => 'delete_photo',
            'user_id' => $this->targetUser->id,
        ]);

        $response->assertOk();
    }

    public function test_user_cannot_delete_other_user_photo(): void
    {
        $otherUser = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($otherUser);

        $response = $this->postJson(route('users.ajax'), [
            'action' => 'delete_photo',
            'user_id' => $this->targetUser->id,
        ]);

        $response->assertForbidden();
    }

    // ===== ajaxUploadPhoto tests =====

    public function test_admin_can_upload_user_photo(): void
    {
        $this->actingAs($this->admin);
        Storage::fake('public');

        $response = $this->postJson(route('users.ajax'), [
            'action' => 'upload_photo',
            'user_id' => $this->targetUser->id,
            'photo' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
        ]);

        $response->assertOk();
    }

    public function test_user_can_upload_own_photo(): void
    {
        $this->actingAs($this->targetUser);
        Storage::fake('public');

        $response = $this->postJson(route('users.ajax'), [
            'action' => 'upload_photo',
            'user_id' => $this->targetUser->id,
            'photo' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
        ]);

        $response->assertOk();
    }

    public function test_upload_photo_rejects_non_image(): void
    {
        $this->actingAs($this->admin);
        Storage::fake('public');

        $response = $this->postJson(route('users.ajax'), [
            'action' => 'upload_photo',
            'user_id' => $this->targetUser->id,
            'photo' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

        // Should fail validation
        $this->assertTrue($response->status() >= 400 || $response->json('error') !== null);
    }

    public function test_upload_photo_rejects_oversized_image(): void
    {
        $this->actingAs($this->admin);
        Storage::fake('public');

        $response = $this->postJson(route('users.ajax'), [
            'action' => 'upload_photo',
            'user_id' => $this->targetUser->id,
            'photo' => UploadedFile::fake()->image('avatar.jpg')->size(10000), // 10MB
        ]);

        // Should fail validation for file size
        $this->assertTrue($response->status() >= 400 || $response->json('error') !== null);
    }

    // ===== ajaxResendInvite tests =====

    public function test_admin_can_resend_invite(): void
    {
        $this->actingAs($this->admin);
        Mail::fake();

        $response = $this->postJson(route('users.ajax'), [
            'action' => 'resend_invite',
            'user_id' => $this->targetUser->id,
        ]);

        $response->assertOk();
        Mail::assertSent(\App\Mail\UserInvite::class);
    }

    public function test_non_admin_cannot_resend_invite(): void
    {
        $otherUser = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($otherUser);

        $response = $this->postJson(route('users.ajax'), [
            'action' => 'resend_invite',
            'user_id' => $this->targetUser->id,
        ]);

        $response->assertForbidden();
    }

    // ===== ajaxSendPasswordReset tests =====

    public function test_admin_can_send_password_reset(): void
    {
        $this->actingAs($this->admin);
        Mail::fake();

        $response = $this->postJson(route('users.ajax'), [
            'action' => 'send_password_reset',
            'user_id' => $this->targetUser->id,
        ]);

        $response->assertOk();
    }

    public function test_non_admin_cannot_send_password_reset(): void
    {
        $otherUser = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($otherUser);

        $response = $this->postJson(route('users.ajax'), [
            'action' => 'send_password_reset',
            'user_id' => $this->targetUser->id,
        ]);

        $response->assertForbidden();
    }

    // ===== Authorization tests =====

    public function test_guest_cannot_access_user_ajax(): void
    {
        $response = $this->postJson(route('users.ajax'), [
            'action' => 'delete_photo',
            'user_id' => $this->targetUser->id,
        ]);

        $response->assertUnauthorized();
    }

    public function test_invalid_action_returns_error(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('users.ajax'), [
            'action' => 'invalid_action_xyz',
            'user_id' => $this->targetUser->id,
        ]);

        $this->assertTrue($response->status() >= 400 || $response->json('error') !== null);
    }

    public function test_missing_user_id_returns_error(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('users.ajax'), [
            'action' => 'delete_photo',
        ]);

        $this->assertTrue($response->status() >= 400 || $response->json('error') !== null);
    }

    public function test_nonexistent_user_returns_error(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('users.ajax'), [
            'action' => 'delete_photo',
            'user_id' => 99999,
        ]);

        $this->assertTrue($response->status() >= 400 || $response->json('error') !== null);
    }
}
