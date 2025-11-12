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
        $conversationFolder = ConversationFolder::create([
            'conversation_id' => 1,
            'folder_id' => 2,
        ]);

        $this->assertInstanceOf(ConversationFolder::class, $conversationFolder);
        $this->assertEquals(1, $conversationFolder->conversation_id);
        $this->assertEquals(2, $conversationFolder->folder_id);
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
        $conversationFolder = ConversationFolder::create([
            'conversation_id' => '123',
            'folder_id' => '456',
        ]);

        $this->assertIsInt($conversationFolder->conversation_id);
        $this->assertEquals(123, $conversationFolder->conversation_id);
    }

    #[Test]
    public function conversation_folder_casts_folder_id_to_integer(): void
    {
        $conversationFolder = ConversationFolder::create([
            'conversation_id' => '123',
            'folder_id' => '456',
        ]);

        $this->assertIsInt($conversationFolder->folder_id);
        $this->assertEquals(456, $conversationFolder->folder_id);
    }

    #[Test]
    public function conversation_folder_casts_timestamps(): void
    {
        $conversationFolder = ConversationFolder::create([
            'conversation_id' => 1,
            'folder_id' => 2,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $conversationFolder->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $conversationFolder->updated_at);
    }

    #[Test]
    public function conversation_folder_can_be_updated(): void
    {
        $conversationFolder = ConversationFolder::create([
            'conversation_id' => 1,
            'folder_id' => 2,
        ]);

        $conversationFolder->update(['folder_id' => 3]);

        $this->assertEquals(3, $conversationFolder->fresh()->folder_id);
    }

    #[Test]
    public function conversation_folder_can_be_deleted(): void
    {
        $conversationFolder = ConversationFolder::create([
            'conversation_id' => 1,
            'folder_id' => 2,
        ]);

        $id = $conversationFolder->id;
        $conversationFolder->delete();

        $this->assertNull(ConversationFolder::find($id));
    }

    #[Test]
    public function multiple_conversation_folders_can_exist(): void
    {
        ConversationFolder::create(['conversation_id' => 1, 'folder_id' => 2]);
        ConversationFolder::create(['conversation_id' => 1, 'folder_id' => 3]);
        ConversationFolder::create(['conversation_id' => 2, 'folder_id' => 2]);

        $this->assertEquals(3, ConversationFolder::count());
    }

    #[Test]
    public function conversation_folder_records_creation_time(): void
    {
        $before = now();
        $conversationFolder = ConversationFolder::create([
            'conversation_id' => 1,
            'folder_id' => 2,
        ]);
        $after = now();

        $this->assertTrue($conversationFolder->created_at->between($before, $after));
    }

    #[Test]
    public function conversation_folder_records_update_time(): void
    {
        $conversationFolder = ConversationFolder::create([
            'conversation_id' => 1,
            'folder_id' => 2,
        ]);

        $originalUpdatedAt = $conversationFolder->updated_at;
        
        sleep(1);
        $conversationFolder->update(['folder_id' => 3]);

        $this->assertTrue($conversationFolder->updated_at->isAfter($originalUpdatedAt));
    }

    #[Test]
    public function conversation_folder_can_be_queried_by_conversation_id(): void
    {
        ConversationFolder::create(['conversation_id' => 1, 'folder_id' => 2]);
        ConversationFolder::create(['conversation_id' => 1, 'folder_id' => 3]);
        ConversationFolder::create(['conversation_id' => 2, 'folder_id' => 2]);

        $folders = ConversationFolder::where('conversation_id', 1)->get();

        $this->assertCount(2, $folders);
    }

    #[Test]
    public function conversation_folder_can_be_queried_by_folder_id(): void
    {
        ConversationFolder::create(['conversation_id' => 1, 'folder_id' => 2]);
        ConversationFolder::create(['conversation_id' => 2, 'folder_id' => 2]);
        ConversationFolder::create(['conversation_id' => 3, 'folder_id' => 3]);

        $folders = ConversationFolder::where('folder_id', 2)->get();

        $this->assertCount(2, $folders);
    }
}
