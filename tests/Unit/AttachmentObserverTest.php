<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Attachment;
use App\Models\Thread;
use Illuminate\Support\Facades\Storage;
use Tests\UnitTestCase;

class AttachmentObserverTest extends UnitTestCase
{

    public function test_deleting_removes_file_from_storage(): void
    {
        Storage::fake('local');

        $thread = Thread::factory()->create();
        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
            'file_dir' => 'attachments',
            'file_name' => 'test.pdf',
        ]);

        // Create a fake file
        Storage::put('attachments/test.pdf', 'test content');

        $this->assertTrue(Storage::exists('attachments/test.pdf'));

        $attachment->delete();

        $this->assertFalse(Storage::exists('attachments/test.pdf'));
    }

    public function test_deleting_handles_missing_file(): void
    {
        Storage::fake('local');

        $thread = Thread::factory()->create();
        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
            'file_dir' => 'attachments',
            'file_name' => 'missing.pdf',
        ]);

        // Don't create the file
        $this->assertFalse(Storage::exists('attachments/missing.pdf'));

        // Should not throw an error
        $attachment->delete();

        $this->assertTrue(true);
    }
}
