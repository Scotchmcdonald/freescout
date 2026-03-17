<?php

declare(strict_types=1);

namespace Tests\Integration\Observers;

use App\Models\Attachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_file_from_storage_when_attachment_deleted()
    {
        $attachment = Attachment::factory()->create([
            'file_dir' => 'attachments/2024',
            'file_name' => 'test.pdf',
        ]);

        $filePath = 'attachments/2024/test.pdf';
        Storage::disk('local')->put($filePath, 'test content');

        $this->assertTrue(Storage::disk('local')->exists($filePath), 'File should exist');

        $attachment->delete();

        $this->assertFalse(Storage::disk('local')->exists($filePath), 'File not deleted');
    }

    public function test_it_handles_missing_file_gracefully()
    {
        $attachment = Attachment::factory()->create([
            'file_dir' => 'attachments/2024',
            'file_name' => 'nonexistent.pdf',
        ]);

        // File doesn't exist
        $this->assertFalse(Storage::disk('local')->exists('attachments/2024/nonexistent.pdf'));

        // Should not throw exception
        $attachment->delete();

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }

    public function test_it_only_deletes_own_file()
    {
        $attachment1 = Attachment::factory()->create([
            'file_dir' => 'attachments/2024',
            'file_name' => 'file1.pdf',
        ]);

        $attachment2 = Attachment::factory()->create([
            'file_dir' => 'attachments/2024',
            'file_name' => 'file2.pdf',
        ]);

        $filePath1 = 'attachments/2024/file1.pdf';
        $filePath2 = 'attachments/2024/file2.pdf';

        Storage::disk('local')->put($filePath1, 'content 1');
        Storage::disk('local')->put($filePath2, 'content 2');

        $attachment1->delete();

        $this->assertFalse(Storage::disk('local')->exists($filePath1));
        $this->assertTrue(Storage::disk('local')->exists($filePath2));
    }

    public function test_it_handles_attachment_without_file_dir()
    {
        Storage::fake('local');

        $attachment = Attachment::factory()->create([
            'file_dir' => '',
            'file_name' => 'test.pdf',
        ]);

        // Should not throw exception when file_dir is empty
        $attachment->delete();

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }

    public function test_it_handles_attachment_without_file_name()
    {
        Storage::fake('local');

        $attachment = Attachment::factory()->create([
            'file_dir' => 'attachments/2024',
            'file_name' => '',
        ]);

        // Should not throw exception when file_name is empty
        $attachment->delete();

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }
}
