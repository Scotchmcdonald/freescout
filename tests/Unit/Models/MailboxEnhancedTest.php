<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailboxEnhancedTest extends TestCase
{
    use RefreshDatabase;

    public function test_mailbox_has_many_conversations(): void
    {
        $mailbox = Mailbox::factory()->create();
        Conversation::factory()->count(3)->create(['mailbox_id' => $mailbox->id]);

        $this->assertCount(3, $mailbox->conversations);
        $this->assertInstanceOf(Conversation::class, $mailbox->conversations->first());
    }

    public function test_mailbox_has_many_folders(): void
    {
        $mailbox = Mailbox::factory()->create();
        Folder::factory()->count(2)->create(['mailbox_id' => $mailbox->id]);

        $this->assertCount(2, $mailbox->folders);
        $this->assertInstanceOf(Folder::class, $mailbox->folders->first());
    }

    public function test_mailbox_belongs_to_many_users(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $mailbox->users()->attach([$user1->id, $user2->id]);

        $this->assertCount(2, $mailbox->users);
    }

    public function test_mailbox_has_in_server_configuration(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
        ]);

        $this->assertEquals('imap.example.com', $mailbox->in_server);
        $this->assertEquals(993, $mailbox->in_port);
        $this->assertEquals('test@example.com', $mailbox->in_username);
    }

    public function test_mailbox_has_out_server_configuration(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'test@example.com',
        ]);

        $this->assertEquals('smtp.example.com', $mailbox->out_server);
        $this->assertEquals(587, $mailbox->out_port);
        $this->assertEquals('test@example.com', $mailbox->out_username);
    }

    public function test_mailbox_eager_loads_relationships(): void
    {
        $mailbox = Mailbox::factory()->create();
        Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        Folder::factory()->create(['mailbox_id' => $mailbox->id]);

        $loaded = Mailbox::with(['conversations', 'folders', 'users'])->first();

        $this->assertTrue($loaded->relationLoaded('conversations'));
        $this->assertTrue($loaded->relationLoaded('folders'));
        $this->assertTrue($loaded->relationLoaded('users'));
    }
}
