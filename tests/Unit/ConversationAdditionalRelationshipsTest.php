<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationAdditionalRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function conversation_belongs_to_many_folders(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        
        $folder1 = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        
        $folder2 = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        // Act
        $conversation->folders()->attach([$folder1->id, $folder2->id]);

        // Assert
        $folders = $conversation->folders;
        $this->assertCount(2, $folders);
        $this->assertTrue($folders->contains($folder1));
        $this->assertTrue($folders->contains($folder2));
    }

    /** @test */
    public function conversation_belongs_to_many_followers(): void
    {
        // Arrange
        $conversation = Conversation::factory()->create();
        
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Act
        $conversation->followers()->attach([$user1->id, $user2->id]);

        // Assert
        $followers = $conversation->followers;
        $this->assertCount(2, $followers);
        $this->assertTrue($followers->contains($user1));
        $this->assertTrue($followers->contains($user2));
    }

    /** @test */
    public function conversation_followers_have_timestamps(): void
    {
        // Arrange
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();

        // Act
        $conversation->followers()->attach($user->id);
        
        // Assert
        $follower = $conversation->followers()->first();
        $this->assertNotNull($follower->pivot->created_at);
        $this->assertNotNull($follower->pivot->updated_at);
    }

    /** @test */
    public function conversation_can_add_and_remove_followers(): void
    {
        // Arrange
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();

        // Act - Add follower
        $conversation->followers()->attach($user->id);
        $this->assertCount(1, $conversation->followers);

        // Act - Remove follower
        $conversation->followers()->detach($user->id);
        $conversation->refresh();
        $this->assertCount(0, $conversation->followers);
    }

    /** @test */
    public function conversation_folders_relationship_has_timestamps(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        // Act
        $conversation->folders()->attach($folder->id);
        
        // Assert
        $attachedFolder = $conversation->folders()->first();
        $this->assertNotNull($attachedFolder->pivot->created_at);
        $this->assertNotNull($attachedFolder->pivot->updated_at);
    }
}
