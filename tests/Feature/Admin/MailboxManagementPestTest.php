<?php

use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('mailbox list page loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get('/mailboxes')
        ->assertOk();
});

it('mailbox create page loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get('/mailboxes/create')
        ->assertOk();
});

it('mailbox settings page loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($admin)
        ->get('/mailbox/'.$mailbox->id.'/settings')
        ->assertOk();
});
