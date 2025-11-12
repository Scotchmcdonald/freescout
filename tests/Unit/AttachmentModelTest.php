<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Attachment;
use App\Models\Thread;
use Tests\UnitTestCase;

class AttachmentModelTest extends UnitTestCase
{

    public function test_model_can_be_instantiated(): void
    {
        $attachment = new Attachment;
        $this->assertInstanceOf(Attachment::class, $attachment);
    }

    public function test_model_has_fillable_attributes(): void
    {
        $attachment = new Attachment([
            'thread_id' => 1,
            'mime_type' => 'text/plain',
        ]);

        $this->assertEquals(1, $attachment->thread_id);
        $this->assertEquals('text/plain', $attachment->mime_type);
    }

    public function test_filename_size_and_mime_type_are_stored(): void
    {
        $attachment = Attachment::factory()->create([
            'file_name' => 'document.pdf',
            'file_size' => 2048,
            'mime_type' => 'application/pdf',
        ]);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
            'file_name' => 'document.pdf',
            'file_size' => 2048,
            'mime_type' => 'application/pdf',
        ]);
    }

    public function test_embedded_cast_to_boolean(): void
    {
        $attachment = Attachment::factory()->create([
            'embedded' => true,
        ]);

        $this->assertIsBool($attachment->embedded);
        $this->assertTrue($attachment->embedded);

        $attachmentNotEmbedded = Attachment::factory()->create([
            'embedded' => false,
        ]);

        $this->assertFalse($attachmentNotEmbedded->embedded);
    }

    public function test_image_mime_types_can_be_detected(): void
    {
        $imageAttachment = Attachment::factory()->create(['mime_type' => 'image/jpeg']);
        $this->assertTrue(str_starts_with($imageAttachment->mime_type, 'image/'));

        $pdfAttachment = Attachment::factory()->create(['mime_type' => 'application/pdf']);
        $this->assertFalse(str_starts_with($pdfAttachment->mime_type, 'image/'));
    }

    public function test_is_image_method_works(): void
    {
        $imageAttachment = Attachment::factory()->image()->create();
        $this->assertTrue($imageAttachment->isImage());

        $pdfAttachment = Attachment::factory()->pdf()->create();
        $this->assertFalse($pdfAttachment->isImage());
    }

    public function test_belongs_to_thread(): void
    {
        $thread = Thread::factory()->create();
        $attachment = Attachment::factory()->for($thread)->create();

        $this->assertInstanceOf(Thread::class, $attachment->thread);
        $this->assertEquals($thread->id, $attachment->thread->id);
    }
}
