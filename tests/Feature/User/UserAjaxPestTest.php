<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

test('admin can delete user photo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    
    $this->actingAs($admin);
    Storage::fake('public');

    $user->photo_url = 'photos/test.jpg';
    $user->save();

    $this->postJson(route('users.ajax'), [
        'action' => 'delete_photo',
        'user_id' => $user->id,
    ])->assertOk();
});

test('user can delete own photo', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    
    $this->actingAs($user);
    Storage::fake('public');

    $user->photo_url = 'photos/test.jpg';
    $user->save();

    $this->postJson(route('users.ajax'), [
        'action' => 'delete_photo',
        'user_id' => $user->id,
    ])->assertOk();
});

test('user cannot delete other user photo', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $otherUser = User::factory()->create(['role' => User::ROLE_USER]);
    
    $this->actingAs($user);

    $this->postJson(route('users.ajax'), [
        'action' => 'delete_photo',
        'user_id' => $otherUser->id,
    ])->assertForbidden();
});

test('admin can upload user photo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    
    $this->actingAs($admin);
    Storage::fake('public');

    $this->postJson(route('users.ajax'), [
        'action' => 'upload_photo',
        'user_id' => $user->id,
        'photo' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
    ])->assertOk();
});

test('user can upload own photo', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    
    $this->actingAs($user);
    Storage::fake('public');

    $this->postJson(route('users.ajax'), [
        'action' => 'upload_photo',
        'user_id' => $user->id,
        'photo' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
    ])->assertOk();
});

test('upload photo rejects non image', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    
    $this->actingAs($admin);
    Storage::fake('public');

    $response = $this->postJson(route('users.ajax'), [
        'action' => 'upload_photo',
        'user_id' => $user->id,
        'photo' => UploadedFile::fake()->create('document.pdf', 100),
    ]);

    expect($response->status() >= 400 || $response->json('error') !== null)->toBeTrue();
});

test('upload photo rejects oversized image', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    
    $this->actingAs($admin);
    Storage::fake('public');

    $response = $this->postJson(route('users.ajax'), [
        'action' => 'upload_photo',
        'user_id' => $user->id,
        'photo' => UploadedFile::fake()->image('avatar.jpg')->size(10000),
    ]);

    expect($response->status() >= 400 || $response->json('error') !== null)->toBeTrue();
});

test('admin can resend invite', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create([
        'role' => User::ROLE_USER,
        'invite_hash' => 'test-hash',
        'invite_state' => User::INVITE_STATE_SENT,
    ]);
    
    $this->actingAs($admin);
    Mail::fake();

    $this->postJson(route('users.ajax'), [
        'action' => 'resend_invite',
        'user_id' => $user->id,
    ])->assertOk();

    Mail::assertSent(\App\Mail\UserInvite::class);
});

test('non admin cannot resend invite', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $targetUser = User::factory()->create([
        'role' => User::ROLE_USER,
        'invite_hash' => 'test-hash',
    ]);
    
    $this->actingAs($user);

    $this->postJson(route('users.ajax'), [
        'action' => 'resend_invite',
        'user_id' => $targetUser->id,
    ])->assertForbidden();
});

test('admin can send password reset', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    
    $this->actingAs($admin);
    Mail::fake();

    $this->postJson(route('users.ajax'), [
        'action' => 'send_password_reset',
        'user_id' => $user->id,
    ])->assertOk();
});

test('non admin cannot send password reset', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $targetUser = User::factory()->create(['role' => User::ROLE_USER]);
    
    $this->actingAs($user);

    $this->postJson(route('users.ajax'), [
        'action' => 'send_password_reset',
        'user_id' => $targetUser->id,
    ])->assertForbidden();
});

test('guest cannot access user ajax', function () {
    $this->postJson(route('users.ajax'), [
        'action' => 'delete_photo',
        'user_id' => 1,
    ])->assertUnauthorized();
});

test('invalid action returns error', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    
    $this->actingAs($admin);

    $response = $this->postJson(route('users.ajax'), [
        'action' => 'invalid_action_xyz',
        'user_id' => $user->id,
    ]);

    expect($response->status() >= 400 || $response->json('error') !== null)->toBeTrue();
});

test('missing user id returns error', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    
    $this->actingAs($admin);

    $response = $this->postJson(route('users.ajax'), [
        'action' => 'delete_photo',
    ]);

    expect($response->status() >= 400 || $response->json('error') !== null)->toBeTrue();
});

test('nonexistent user returns error', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    
    $this->actingAs($admin);

    $response = $this->postJson(route('users.ajax'), [
        'action' => 'delete_photo',
        'user_id' => 99999,
    ]);

    expect($response->status() >= 400 || $response->json('error') !== null)->toBeTrue();
});
