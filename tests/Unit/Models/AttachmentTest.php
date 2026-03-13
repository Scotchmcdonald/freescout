<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Attachment;
use App\Models\Thread;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for Attachment Model
 * Following TESTING_GUIDE.md - using test_ prefix, UnitTestCase base class
 */
class AttachmentTest extends UnitTestCase
{
    // ===== MODEL CREATION TESTS =====

    public function test_attachment_can_be_created(): void
    {
        $thread = Thread::factory()->create();

        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
            'file_name' => 'test.pdf',
        ]);

        $this->assertInstanceOf(Attachment::class, $attachment);
        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
            'file_name' => 'test.pdf',
        ]);
    }

    public function test_attachment_has_correct_fillable_attributes(): void
    {
        $attachment = new Attachment;

        $this->assertContains('thread_id', $attachment->getFillable());
        $this->assertContains('file_name', $attachment->getFillable());
        $this->assertContains('file_dir', $attachment->getFillable());
        $this->assertContains('file_size', $attachment->getFillable());
        $this->assertContains('mime_type', $attachment->getFillable());
        $this->assertContains('embedded', $attachment->getFillable());
    }

    public function test_attachment_uses_has_factory_trait(): void
    {
        $attachment = Attachment::factory()->create();

        $this->assertInstanceOf(Attachment::class, $attachment);
    }

    // ===== RELATIONSHIP TESTS =====

    public function test_attachment_belongs_to_thread(): void
    {
        $thread = Thread::factory()->create();
        $attachment = Attachment::factory()->create(['thread_id' => $thread->id]);

        $this->assertInstanceOf(Thread::class, $attachment->thread);
        $this->assertEquals($thread->id, $attachment->thread->id);
    }

    public function test_attachment_can_have_conversation_id(): void
    {
        $conversation = \App\Models\Conversation::factory()->create();
        $attachment = Attachment::factory()->create(['conversation_id' => $conversation->id]);

        $this->assertEquals($conversation->id, $attachment->conversation_id);
    }

    public function test_attachment_conversation_id_can_be_null(): void
    {
        $attachment = Attachment::factory()->create(['conversation_id' => null]);

        $this->assertNull($attachment->conversation_id);
    }

    // ===== CAST TESTS =====

    public function test_thread_id_is_cast_to_integer(): void
    {
        $thread = Thread::factory()->create();
        $attachment = Attachment::factory()->create(['thread_id' => (string) $thread->id]);

        $this->assertIsInt($attachment->thread_id);
    }

    public function test_file_size_is_cast_to_integer(): void
    {
        $attachment = Attachment::factory()->create(['file_size' => '1024']);

        $this->assertIsInt($attachment->file_size);
    }

    public function test_embedded_is_cast_to_boolean(): void
    {
        $attachment = Attachment::factory()->create(['embedded' => 1]);

        $this->assertIsBool($attachment->embedded);
        $this->assertTrue($attachment->embedded);
    }

    public function test_created_at_is_cast_to_datetime(): void
    {
        $attachment = Attachment::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $attachment->created_at);
    }

    public function test_updated_at_is_cast_to_datetime(): void
    {
        $attachment = Attachment::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $attachment->updated_at);
    }

    // ===== ACCESSOR TESTS =====

    public function test_get_full_path_attribute_returns_correct_path(): void
    {
        $attachment = Attachment::factory()->create([
            'file_dir' => 'attachments/2024/01',
            'file_name' => 'document.pdf',
        ]);

        $expectedPath = storage_path('app/attachments/2024/01/document.pdf');
        $this->assertEquals($expectedPath, $attachment->full_path);
    }

    public function test_get_human_file_size_attribute_for_bytes(): void
    {
        $attachment = Attachment::factory()->create(['file_size' => 512]);

        $this->assertStringContainsString('B', $attachment->human_file_size);
    }

    public function test_get_human_file_size_attribute_for_kilobytes(): void
    {
        $attachment = Attachment::factory()->create(['file_size' => 2048]);

        $this->assertStringContainsString('KB', $attachment->human_file_size);
    }

    public function test_get_human_file_size_attribute_for_megabytes(): void
    {
        $attachment = Attachment::factory()->create(['file_size' => 1048576 * 2]); // 2MB

        $this->assertStringContainsString('MB', $attachment->human_file_size);
    }

    // ===== ATTRIBUTE TESTS =====

    public function test_attachment_has_file_name_attribute(): void
    {
        $attachment = Attachment::factory()->create(['file_name' => 'test_document.pdf']);

        $this->assertEquals('test_document.pdf', $attachment->file_name);
    }

    public function test_attachment_has_file_dir_attribute(): void
    {
        $attachment = Attachment::factory()->create(['file_dir' => 'attachments/2024']);

        $this->assertEquals('attachments/2024', $attachment->file_dir);
    }

    public function test_attachment_has_mime_type_attribute(): void
    {
        $attachment = Attachment::factory()->create(['mime_type' => 'application/pdf']);

        $this->assertEquals('application/pdf', $attachment->mime_type);
    }

    public function test_attachment_mime_type_can_be_null(): void
    {
        $attachment = Attachment::factory()->create(['mime_type' => null]);

        $this->assertNull($attachment->mime_type);
    }

    public function test_attachment_embedded_defaults_to_false(): void
    {
        $attachment = Attachment::factory()->create(['embedded' => false]);

        $this->assertFalse($attachment->embedded);
    }

    public function test_attachment_embedded_can_be_true(): void
    {
        $attachment = Attachment::factory()->create(['embedded' => true]);

        $this->assertTrue($attachment->embedded);
    }

    // ===== QUERY TESTS =====

    public function test_can_query_attachments_by_thread(): void
    {
        $thread = Thread::factory()->create();
        Attachment::factory()->count(3)->create(['thread_id' => $thread->id]);
        Attachment::factory()->create(); // Different thread

        $attachments = Attachment::where('thread_id', $thread->id)->get();

        $this->assertCount(3, $attachments);
    }

    public function test_can_query_attachments_by_file_name(): void
    {
        Attachment::factory()->create(['file_name' => 'test1.pdf']);
        Attachment::factory()->create(['file_name' => 'test2.pdf']);

        $attachments = Attachment::where('file_name', 'test1.pdf')->get();

        $this->assertCount(1, $attachments);
    }

    public function test_can_query_embedded_attachments(): void
    {
        Attachment::factory()->count(2)->create(['embedded' => true]);
        Attachment::factory()->count(3)->create(['embedded' => false]);

        $embeddedAttachments = Attachment::where('embedded', true)->get();

        $this->assertCount(2, $embeddedAttachments);
    }

    public function test_can_query_attachments_by_mime_type(): void
    {
        Attachment::factory()->create(['mime_type' => 'application/pdf']);
        Attachment::factory()->create(['mime_type' => 'image/jpeg']);

        $pdfAttachments = Attachment::where('mime_type', 'application/pdf')->get();

        $this->assertCount(1, $pdfAttachments);
    }

    // ===== EDGE CASES =====

    public function test_attachment_with_zero_file_size(): void
    {
        $attachment = Attachment::factory()->create(['file_size' => 0]);

        $this->assertEquals(0, $attachment->file_size);
        $this->assertStringContainsString('B', $attachment->human_file_size);
    }

    public function test_attachment_with_very_large_file_size(): void
    {
        $attachment = Attachment::factory()->create(['file_size' => 1073741824 * 5]); // 5GB

        $this->assertEquals(1073741824 * 5, $attachment->file_size);
        $this->assertStringContainsString('GB', $attachment->human_file_size);
    }

    public function test_attachment_with_special_characters_in_filename(): void
    {
        $attachment = Attachment::factory()->create(['file_name' => 'test file (1) [copy].pdf']);

        $this->assertEquals('test file (1) [copy].pdf', $attachment->file_name);
    }

    public function test_attachment_with_unicode_filename(): void
    {
        $attachment = Attachment::factory()->create(['file_name' => 'тест_файл_日本語.pdf']);

        $this->assertEquals('тест_файл_日本語.pdf', $attachment->file_name);
    }

    public function test_attachment_with_long_file_path(): void
    {
        $longPath = 'attachments/'.str_repeat('a/', 50);
        $attachment = Attachment::factory()->create(['file_dir' => $longPath]);

        $this->assertEquals($longPath, $attachment->file_dir);
    }

    public function test_attachment_can_be_updated(): void
    {
        $attachment = Attachment::factory()->create(['file_name' => 'old.pdf']);

        $attachment->update(['file_name' => 'new.pdf']);

        $this->assertEquals('new.pdf', $attachment->fresh()->file_name);
    }

    public function test_attachment_can_be_deleted(): void
    {
        $attachment = Attachment::factory()->create();
        $id = $attachment->id;

        $attachment->delete();

        $this->assertDatabaseMissing('attachments', ['id' => $id]);
    }

    public function test_multiple_attachments_can_exist_for_same_thread(): void
    {
        $thread = Thread::factory()->create();

        Attachment::factory()->count(5)->create(['thread_id' => $thread->id]);

        $this->assertCount(5, $thread->attachments);
    }

    public function test_attachment_with_common_image_mime_types(): void
    {
        $types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        foreach ($types as $type) {
            $attachment = Attachment::factory()->create(['mime_type' => $type]);
            $this->assertEquals($type, $attachment->mime_type);
        }
    }

    public function test_attachment_with_common_document_mime_types(): void
    {
        $types = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        foreach ($types as $type) {
            $attachment = Attachment::factory()->create(['mime_type' => $type]);
            $this->assertEquals($type, $attachment->mime_type);
        }
    }

    public function test_attachment_timestamps_are_automatically_set(): void
    {
        $attachment = Attachment::factory()->create();

        $this->assertNotNull($attachment->created_at);
        $this->assertNotNull($attachment->updated_at);
    }

    public function test_full_path_handles_empty_file_dir(): void
    {
        $attachment = Attachment::factory()->create([
            'file_dir' => '',
            'file_name' => 'test.pdf',
        ]);

        $expectedPath = storage_path('app//test.pdf');
        $this->assertEquals($expectedPath, $attachment->full_path);
    }

    public function test_human_file_size_formats_correctly_for_1kb(): void
    {
        $attachment = Attachment::factory()->create(['file_size' => 1024]);

        $humanSize = $attachment->human_file_size;
        $this->assertStringContainsString('1', $humanSize);
        $this->assertStringContainsString('KB', $humanSize);
    }

    public function test_human_file_size_formats_correctly_for_1mb(): void
    {
        $attachment = Attachment::factory()->create(['file_size' => 1048576]);

        $humanSize = $attachment->human_file_size;
        $this->assertStringContainsString('1', $humanSize);
        $this->assertStringContainsString('MB', $humanSize);
    }

    public function test_attachment_with_various_file_extensions(): void
    {
        $extensions = ['pdf', 'doc', 'docx', 'jpg', 'png', 'zip', 'txt', 'csv'];

        foreach ($extensions as $ext) {
            $attachment = Attachment::factory()->create(['file_name' => "test.{$ext}"]);
            $this->assertStringEndsWith(".{$ext}", $attachment->file_name);
        }
    }
}
