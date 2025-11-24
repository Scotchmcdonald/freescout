<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_attachment_download_works_with_valid_signature()
    {
        Storage::fake('local');
        
        $attachment = Attachment::factory()->create([
            'file_dir' => 'attachments',
            'file_name' => 'test.txt',
            'file_size' => 123,
        ]);
        
        Storage::put('attachments/test.txt', 'content');

        $url = URL::signedRoute('attachments.public_download', ['id' => $attachment->id]);

        $response = $this->get($url);

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=test.txt');
    }

    public function test_public_attachment_download_fails_with_invalid_signature()
    {
        $attachment = Attachment::factory()->create();
        
        $url = route('attachments.public_download', ['id' => $attachment->id]); // Unsigned

        $response = $this->get($url);

        $response->assertStatus(403);
    }

    public function test_tracking_pixel_works_with_valid_signature()
    {
        $thread = Thread::factory()->create(['opened_at' => null]);

        $url = URL::signedRoute('track.pixel', ['id' => $thread->id]);

        $response = $this->get($url);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'image/gif');
        
        $this->assertNotNull($thread->fresh()->opened_at);
    }

    public function test_tracking_pixel_fails_with_invalid_signature()
    {
        $thread = Thread::factory()->create();

        $url = route('track.pixel', ['id' => $thread->id]); // Unsigned

        $response = $this->get($url);

        $response->assertStatus(403);
    }
}
