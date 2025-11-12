<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\ConversationFolder;
use App\Models\Folder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConversationFolderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function conversation_folder_can_be_created(): void
    {
        $conversation = Conversation::factory()->create();
        $folder = Folder::factory()->create();

        $conversationFolder = ConversationFolder::create([
            'conversation_id' => $conversation->id,
            'folder_id' => $folder->id,
        ]);

        $this->assertInstanceOf(ConversationFolder::class, $conversationFolder);
        $this->assertEquals($conversation->id, $conversationFolder->conversation_id);
        $this->assertEquals($folder->id, $conversationFolder->folder_id);
    }

    #[Test]
    public function conversation_folder_is_a_pivot_model(): void
    {
        $conversationFolder = new ConversationFolder();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\Pivot::class, $conversationFolder);
    }

    #[Test]
    public function conversation_folder_uses_correct_table(): void
    {
        $conversationFolder = new ConversationFolder();

        $this->assertEquals('conversation_folder', $conversationFolder->getTable());
    }

    #[Test]
    public function conversation_folder_has_timestamps(): void
    {
        $conversationFolder = new ConversationFolder();

        $this->assertTrue($conversationFolder->timestamps);
    }

    #[Test]
    public function conversation_folder_has_fillable_attributes(): void
    {
        $conversationFolder = new ConversationFolder();

        $this->assertEquals(['conversation_id', 'folder_id'], $conversationFolder->getFillable());
    }

    #[Test]
    public function conversation_folder_casts_conversation_id_to_integer(): void
    {
        $conversation = Conversation::factory()->create();
        $folder = Folder::factory()->create();

        $conversationFolder = ConversationFolder::create([
            'conversation_id' => (string) $conversation->id,
            'folder_id' => (string) $folder->id,
        ]);

        $this->assertIsInt($conversationFolder->conversation_id);
    }

    ##[Test]
    public function conversation_folder_casts_folder_id_to_integer(): void
    {
        $conversation = Conversation::factory()->create();
        $folder = Folder::factory()->create();

        $conversationFolder = ConversationFolder::create([
            'conversation_id' => (string) $conversation->id,
            'folder_id' => (string) $folder->id,
        ]);

        $this->assertIsInt($conversationFolder->folder_id);
    }

    #[Test]
    public function conversation_folder_casts_timestamps(): void
    {
        $conversation = Conversation::factory()->create();
        $folder = Folder::factory()->create();

        $conversationFolder = ConversationFolder::create([
            'conversation_id' => $conversation->id,
            'folder_id' => $folder->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $conversationFolder->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $conversationFolder->updated_at);
    }

    #[Test]
    public function conversation_folder_can_be_moved_to_different_folder(): void
    {
        $conversation = Conversation::factory()->create();
        $folder1 = Folder::factory()->create();
        $folder2 = Folder::factory()->create();

        ConversationFolder::create([
            'conversation_id' => $conversation->id,
            'folder_id' => $folder1->id,
        ]);

        // Delete old association using query builder
        ConversationFolder::where('conversation_id', $conversation->id)
            ->where('folder_id', $folder1->id)
            ->delete();
            
        // Create new association
        ConversationFolder::create([
            'conversation_id' => $conversation->id,
            'folder_id' => $folder2->id,
        ]);

        // Verify the new association exists
        $this->assertDatabaseHas('conversation_folder', [
            'conversation_id' => $conversation->id,
            'folder_id' => $folder2->id,
        ]);
        
        // Verify old association is gone
        $this->assertDatabaseMissing('conversation_folder', [
            'conversation_id' => $conversation->id,
            'folder_id' => $folder1->id,
        ]);
    }

    #[Test]
    public function conversation_folder_can_be_deleted(): void
    {
        $conversation = Conversation::factory()->create();
        $folder = Folder::factory()->create();

        $conversationFolder = ConversationFolder::create([
            'conversation_id' => $conversation->id,
            'folder_id' => $folder->id,
        ]);

        $id = $conversationFolder->id;
        $conversationFolder->delete();

        $this->assertNull(ConversationFolder::find($id));
    }

    #[Test]
    public function multiple_conversation_folders_can_exist(): void
    {
        $conversation1 = Conversation::factory()->create();
        $conversation2 = Conversation::factory()->create();
        $folder1 = Folder::factory()->create();
        $folder2 = Folder::factory()->create();

        ConversationFolder::create(['conversation_id' => $conversation1->id, 'folder_id' => $folder1->id]);
        ConversationFolder::create(['conversation_id' => $conversation1->id, 'folder_id' => $folder2->id]);
        ConversationFolder::create(['conversation_id' => $conversation2->id, 'folder_id' => $folder1->id]);

        $this->assertEquals(3, ConversationFolder::count());
    }

    #[Test]
    public function conversation_folder_records_creation_time(): void
    {
        $conversation = Conversation::factory()->create();
        $folder = Folder::factory()->create();

        $before = now()->subSecond();
        $conversationFolder = ConversationFolder::create([
            'conversation_id' => $conversation->id,
            'folder_id' => $folder->id,
        ]);
        $after = now()->addSecond();

        $this->assertTrue($conversationFolder->created_at->between($before, $after));
    }

    #[Test]
    public function conversation_folder_records_update_time(): void
    {
        $conversation = Conversation::factory()->create();
        $folder1 = Folder::factory()->create();
        $folder2 = Folder::factory()->create();

        $conversationFolder = ConversationFolder::create([
            'conversation_id' => $conversation->id,
            'folder_id' => $folder1->id,
        ]);

        $originalUpdatedAt = $conversationFolder->updated_at;
        
        sleep(1);
        $conversationFolder->update(['folder_id' => $folder2->id]);

        $this->assertTrue($conversationFolder->updated_at->isAfter($originalUpdatedAt));
    }

    #[Test]
    public function conversation_folder_can_be_queried_by_conversation_id(): void
    {
        $conversation1 = Conversation::factory()->create();
        $conversation2 = Conversation::factory()->create();
        $folder1 = Folder::factory()->create();
        $folder2 = Folder::factory()->create();

        ConversationFolder::create(['conversation_id' => $conversation1->id, 'folder_id' => $folder1->id]);
        ConversationFolder::create(['conversation_id' => $conversation1->id, 'folder_id' => $folder2->id]);
        ConversationFolder::create(['conversation_id' => $conversation2->id, 'folder_id' => $folder1->id]);

        $folders = ConversationFolder::where('conversation_id', $conversation1->id)->get();

        $this->assertCount(2, $folders);
    }

    #[Test]
    public function conversation_folder_can_be_queried_by_folder_id(): void
    {
        $conversation1 = Conversation::factory()->create();
        $conversation2 = Conversation::factory()->create();
        $conversation3 = Conversation::factory()->create();
        $folder1 = Folder::factory()->create();
        $folder2 = Folder::factory()->create();

        ConversationFolder::create(['conversation_id' => $conversation1->id, 'folder_id' => $folder1->id]);
        ConversationFolder::create(['conversation_id' => $conversation2->id, 'folder_id' => $folder1->id]);
        ConversationFolder::create(['conversation_id' => $conversation3->id, 'folder_id' => $folder2->id]);

        $folders = ConversationFolder::where('folder_id', $folder1->id)->get();

        $this->assertCount(2, $folders);
    }
}
