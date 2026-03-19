<?php

use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('conversation list via mailbox loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create(['name' => 'ConvTestMailbox']);

    $this->actingAs($admin)
        ->get('/mailbox/'.$mailbox->id)
        ->assertOk()
        ->assertSee('ConvTestMailbox');
});

it('conversation search page loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->get('/conversations/search?q=test')
        ->assertOk()
        ->assertSee('Search');
});

it('conversation create page loads for admin', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create(['name' => 'CreateConvMailbox']);

    $this->actingAs($admin)
        ->get('/mailbox/'.$mailbox->id.'/conversation/create')
        ->assertOk()
        ->assertSee('New Conversation');
});
