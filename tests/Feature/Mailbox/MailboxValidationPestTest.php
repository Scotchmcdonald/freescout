<?php

use App\Models\Mailbox;
use App\Models\User;

foreach (['store', 'update'] as $action) {
    test("mailbox $action validates name required", function () use ($action) {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();

        // Pass empty string for name to trigger required validation
        // 'sometimes' rule allows skipping the field, but if present logic applies
        // But UpdateMailboxRequest has 'name' => 'sometimes|required...'
        // If we want to test required, we must send the key.
        $data = [
            'name' => '',
            'email' => 'valid@example.com',
        ];

        $request = $this->actingAs($admin);

        if ($action === 'store') {
            $request->post(route('mailboxes.store'), $data)
                ->assertSessionHasErrors('name');
        } else {
            $request->patch(route('mailboxes.update', $mailbox), $data)
                ->assertSessionHasErrors('name');
        }
    });

    test("mailbox $action validates name max length", function () use ($action) {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();

        $data = [
            'name' => str_repeat('a', 256),
            'email' => 'valid@example.com',
        ];

        $request = $this->actingAs($admin);

        if ($action === 'store') {
            $request->post(route('mailboxes.store'), $data)
                ->assertSessionHasErrors('name');
        } else {
            $request->patch(route('mailboxes.update', $mailbox), $data)
                ->assertSessionHasErrors('name');
        }
    });
}

test('mailbox store validates email required', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->post(route('mailboxes.store'), [
            'name' => 'Test Mailbox',
        ])
        ->assertSessionHasErrors('email');

    $this->assertDatabaseMissing('mailboxes', ['name' => 'Test Mailbox']);
});
