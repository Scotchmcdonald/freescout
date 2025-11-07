<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Attachment;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttachmentModelAccessorsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function attachment_belongs_to_thread(): void
    {
        $thread = Thread::factory()->create();
        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
        ]);

        $this->assertInstanceOf(Thread::class, $attachment->thread);
        $this->assertEquals($thread->id, $attachment->thread->id);
    }

    /** @test */
    public function attachment_full_path_accessor_returns_correct_path(): void
    {
        $attachment = Attachment::factory()->create([
            'filename' => 'test-file.pdf',
            'url' => null,
        ]);

        $expectedPath = storage_path('app/attachments/test-file.pdf');
        $this->assertEquals($expectedPath, $attachment->full_path);
    }

    /** @test */
    public function attachment_human_file_size_accessor_returns_bytes(): void
    {
        $attachment = Attachment::factory()->create([
            'size' => 512,
        ]);

        $this->assertEquals('512 B', $attachment->human_file_size);
    }

    /** @test */
    public function attachment_human_file_size_accessor_returns_kilobytes(): void
    {
        $attachment = Attachment::factory()->create([
            'size' => 2048,
        ]);

        $this->assertEquals('2 KB', $attachment->human_file_size);
    }

    /** @test */
    public function attachment_human_file_size_accessor_returns_megabytes(): void
    {
        $attachment = Attachment::factory()->create([
            'size' => 2097152, // 2 MB
        ]);

        $this->assertEquals('2 MB', $attachment->human_file_size);
    }

    /** @test */
    public function attachment_is_image_returns_true_for_image_mime_type(): void
    {
        $attachment = Attachment::factory()->create([
            'mime_type' => 'image/png',
        ]);

        $this->assertTrue($attachment->isImage());
    }

    /** @test */
    public function attachment_is_image_returns_false_for_non_image_mime_type(): void
    {
        $attachment = Attachment::factory()->create([
            'mime_type' => 'application/pdf',
        ]);

        $this->assertFalse($attachment->isImage());
    }

    /** @test */
    public function attachment_is_image_handles_null_mime_type(): void
    {
        // Use make() to avoid database constraint, testing accessor only
        $attachment = Attachment::factory()->make([
            'mime_type' => null,
        ]);

        $this->assertFalse($attachment->isImage());
    }
}
