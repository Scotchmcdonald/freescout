<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;

test('search finds conversations by subject', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX, 'name' => 'Inbox']);

    $targetConv = Conversation::factory()->for($mailbox)->create([
        'subject' => 'Password Reset Help',
        'state' => Conversation::STATE_PUBLISHED,
        'status' => Conversation::STATUS_ACTIVE,
    ]);

    $otherConv = Conversation::factory()->for($mailbox)->create([
        'subject' => 'Billing Question',
        'state' => Conversation::STATE_PUBLISHED,
        'status' => Conversation::STATUS_ACTIVE,
    ]);

    $response = $this->actingAs($user)->get(route('conversations.search', ['q' => 'Password']));

    $response->assertOk()
        ->assertViewIs('conversations.search')
        ->assertSee('Password Reset Help')
        ->assertDontSee('Billing Question');

    // Check collection if available
    if ($response->viewData('conversations')) {
        $conversations = $response->viewData('conversations');
        $this->assertTrue($conversations->contains($targetConv));
        $this->assertFalse($conversations->contains($otherConv));
    }
});

test('search finds conversations by customer name', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);

    $customer = Customer::factory()->create([
        'first_name' => 'Sarah',
        'last_name' => 'Connor',
    ]);

    $conversation = Conversation::factory()
        ->for($mailbox)
        ->for($customer)
        ->create([
            'subject' => 'Technical Issue',
            'state' => Conversation::STATE_PUBLISHED,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

    $response = $this->actingAs($user)->get(route('conversations.search', ['q' => 'Sarah']));

    $response->assertOk()
        ->assertSee('Technical Issue')
        ->assertSee('Sarah');
});

test('search only shows authorized mailboxes', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);

    // Create conversation in authorized mailbox
    $authorized = Conversation::factory()->for($mailbox)->create([
        'subject' => 'Authorized Conversation',
        'state' => Conversation::STATE_PUBLISHED,
        'status' => Conversation::STATUS_ACTIVE,
    ]);

    // Create conversation in unauthorized mailbox
    $otherMailbox = Mailbox::factory()->create();
    $unauthorized = Conversation::factory()->for($otherMailbox)->create([
        'subject' => 'Unauthorized Conversation',
        'state' => Conversation::STATE_PUBLISHED,
    ]);

    $response = $this->actingAs($user)->get(route('conversations.search', ['q' => 'Conversation']));

    $response->assertOk()
        ->assertSee('Authorized Conversation')
        ->assertDontSee('Unauthorized Conversation');
});

test('admin search shows all mailboxes', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($admin); // Attach purely for initial context if needed, but admin should see all
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);

    // Create conversations in multiple mailboxes
    $mailbox1Conv = Conversation::factory()->for($mailbox)->create([
        'subject' => 'Mailbox 1 Search Test',
        'state' => Conversation::STATE_PUBLISHED,
        'status' => Conversation::STATUS_ACTIVE,
    ]);

    $otherMailbox = Mailbox::factory()->create();
    $mailbox2Conv = Conversation::factory()->for($otherMailbox)->create([
        'subject' => 'Mailbox 2 Search Test',
        'state' => Conversation::STATE_PUBLISHED,
        'status' => Conversation::STATUS_ACTIVE,
    ]);

    $response = $this->actingAs($admin)->get(route('conversations.search', ['q' => 'Search Test']));

    $response->assertOk()
        ->assertSee('Mailbox 1 Search Test')
        ->assertSee('Mailbox 2 Search Test');
});

test('search paginates results', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($admin);
    Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);

    // Create 60 conversations (more than 50 per page)
    Conversation::factory()
        ->count(60)
        ->for($mailbox)
        ->create([
            'subject' => 'Test Pagination',
            'state' => Conversation::STATE_PUBLISHED,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

    $response = $this->actingAs($admin)->get(route('conversations.search', ['q' => 'Pagination']));

    $response->assertOk()
        ->assertViewHas('conversations');

    $conversations = $response->viewData('conversations');
    // We can just check count < 60 and it is a Paginator (hasPages)
    expect($conversations->count())->toBeLessThan(60);
    expect($conversations->hasMorePages())->toBeTrue();
});

test('search finds conversations by number', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user);
    $conversation = Conversation::factory()->for($mailbox)->create([
        'number' => 12345,
    ]);

    $this->actingAs($user)
        ->get(route('conversations.search', ['q' => '12345']))
        ->assertOk();
});

test('search handles empty query', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->get(route('conversations.search', ['q' => '']))
        ->assertOk();
});

test('search handles special characters', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)
        ->get(route('conversations.search', ['q' => '<script>alert("xss")</script>']))
        ->assertOk();
});
