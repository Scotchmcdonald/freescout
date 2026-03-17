<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test Folder model
 *
 * Focus: Types, relationships, basic CRUD
 */
class FolderTest extends TestCase
{
    use RefreshDatabase;

    public function test_folder_belongs_to_mailbox(): void
    {
        $folder = Folder::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $folder->mailbox());
        $this->assertInstanceOf(Mailbox::class, $folder->mailbox);
    }

    public function test_folder_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $folder->user());
        $this->assertEquals($user->id, $folder->user->id);
    }

    public function test_folder_can_be_global(): void
    {
        $folder = Folder::factory()->create(['user_id' => null]);

        $this->assertNull($folder->user_id);
    }

    public function test_folder_can_be_user_specific(): void
    {
        $user = User::factory()->create();
        $folder = Folder::factory()->create(['user_id' => $user->id]);

        $this->assertNotNull($folder->user_id);
        $this->assertEquals($user->id, $folder->user_id);
    }

    public function test_folder_has_required_fillable_fields(): void
    {
        $folder = new Folder;
        $fillable = $folder->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('mailbox_id', $fillable);
    }

    public function test_folder_can_be_created_with_factory(): void
    {
        $folder = Folder::factory()->create([
            'name' => 'Test Folder',
            'type' => 1,
        ]);

        $this->assertDatabaseHas('folders', [
            'id' => $folder->id,
            'name' => 'Test Folder',
            'type' => 1,
        ]);
    }

    public function test_folder_has_timestamps(): void
    {
        $folder = Folder::factory()->create();

        $this->assertNotNull($folder->created_at);
        $this->assertNotNull($folder->updated_at);
    }

    public function test_folder_type_can_be_assigned(): void
    {
        $folder = Folder::factory()->create(['type' => 1]);

        $this->assertEquals(1, $folder->type);
    }

    public function test_folder_type_can_be_unassigned(): void
    {
        $folder = Folder::factory()->create(['type' => 2]);

        $this->assertEquals(2, $folder->type);
    }

    public function test_folder_type_can_be_drafts(): void
    {
        $folder = Folder::factory()->create(['type' => 3]);

        $this->assertEquals(3, $folder->type);
    }

    public function test_folder_type_can_be_deleted(): void
    {
        $folder = Folder::factory()->create(['type' => 4]);

        $this->assertEquals(4, $folder->type);
    }

    public function test_folder_type_can_be_spam(): void
    {
        $folder = Folder::factory()->create(['type' => 30]);

        $this->assertEquals(30, $folder->type);
    }

    public function test_folder_active_count_defaults_to_zero(): void
    {
        $folder = Folder::factory()->create();

        $this->assertEquals(0, $folder->active_count);
    }

    public function test_folder_total_count_defaults_to_zero(): void
    {
        $folder = Folder::factory()->create();

        $this->assertEquals(0, $folder->total_count);
    }

    public function test_folder_with_unicode_name(): void
    {
        $folder = Folder::factory()->create([
            'name' => '受信トレイ Inbox',
        ]);

        $this->assertEquals('受信トレイ Inbox', $folder->name);
    }

    public function test_multiple_folders_can_belong_to_same_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();

        $folder1 = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);
        $folder2 = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 2]);

        $this->assertEquals($mailbox->id, $folder1->mailbox_id);
        $this->assertEquals($mailbox->id, $folder2->mailbox_id);
        $this->assertCount(7, $mailbox->folders); // 5 auto-created + 2 factory
    }
}
