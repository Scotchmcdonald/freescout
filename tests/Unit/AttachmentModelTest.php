<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Attachment;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttachmentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_can_be_instantiated(): void
    {
        $attachment = new Attachment();
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
            'filename' => 'document.pdf',
            'size' => 2048,
            'mime_type' => 'application/pdf',
        ]);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
            'filename' => 'document.pdf',
            'size' => 2048,
            'mime_type' => 'application/pdf',
        ]);
    }

    public function test_inline_and_public_cast_to_boolean(): void
    {
        $attachment = Attachment::factory()->create([
            'inline' => true,
            'public' => false,
        ]);

        $this->assertIsBool($attachment->inline);
        $this->assertIsBool($attachment->public);
        $this->assertTrue($attachment->inline);
        $this->assertFalse($attachment->public);
    }

    public function test_image_mime_types_can_be_detected(): void
    {
        $imageAttachment = Attachment::factory()->create(['mime_type' => 'image/jpeg']);
        $this->assertTrue(str_starts_with($imageAttachment->mime_type, 'image/'));

        $pdfAttachment = Attachment::factory()->create(['mime_type' => 'application/pdf']);
        $this->assertFalse(str_starts_with($pdfAttachment->mime_type, 'image/'));
    }

    public function test_width_and_height_for_images(): void
    {
        $attachment = Attachment::factory()->image()->create();

        $this->assertNotNull($attachment->width);
        $this->assertNotNull($attachment->height);
        $this->assertEquals(1920, $attachment->width);
        $this->assertEquals(1080, $attachment->height);
    }

    public function test_belongs_to_thread(): void
    {
        $thread = Thread::factory()->create();
        $attachment = Attachment::factory()->for($thread)->create();

        $this->assertInstanceOf(Thread::class, $attachment->thread);
        $this->assertEquals($thread->id, $attachment->thread->id);
    }
}
