<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('snooze endpoint increments next follow-up by hours', function () {
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $user->mailboxes()->attach($mailbox->id);

    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'next_follow_up' => now()->setTime(9, 0),
    ]);

    $this->actingAs($user)
        ->patchJson(route('tickets.snooze', $conversation), ['addHours' => 2])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($conversation->fresh()->next_follow_up?->toDateTimeString())
        ->toBe(now()->setTime(11, 0)->toDateTimeString());
});

test('snooze endpoint increments next follow-up by days', function () {
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $user->mailboxes()->attach($mailbox->id);

    $conversation = Conversation::factory()->create([
        'mailbox_id' => $mailbox->id,
        'next_follow_up' => now()->setTime(9, 0),
    ]);

    $this->actingAs($user)
        ->patchJson(route('tickets.snooze', $conversation), ['addDays' => 1])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($conversation->fresh()->next_follow_up?->toDateTimeString())
        ->toBe(now()->addDay()->setTime(9, 0)->toDateTimeString());
});
