<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Attachment;
use Tests\TestCase;

class AttachmentModelTest extends TestCase
{
    public function test_model_can_be_instantiated(): void
    {
        $attachment = new Attachment();
        $this->assertInstanceOf(Attachment::class, $attachment);
    }

    public function test_model_has_fillable_attributes(): void
    {
        $attachment = new Attachment([
            'thread_id' => 1,
            'filename' => 'test.txt',
            'size' => 1024,
            'mime_type' => 'text/plain',
            'inline' => false,
            'public' => false,
        ]);

        $this->assertEquals(1, $attachment->thread_id);
        $this->assertEquals('test.txt', $attachment->filename);
        $this->assertEquals(1024, $attachment->size);
        $this->assertEquals('text/plain', $attachment->mime_type);
        $this->assertEquals(false, $attachment->inline);
        $this->assertEquals(false, $attachment->public);
    }
}
