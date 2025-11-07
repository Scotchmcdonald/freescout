<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConversationUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);
    }

    /** @test */
    public function can_upload_file_via_ajax(): void
    {
        // Arrange
        Storage::fake('public');
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson(route('conversations.upload'), [
                'file' => $file,
            ]);

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'filename',
            'path',
            'size',
        ]);

        $response->assertJson([
            'success' => true,
            'filename' => 'document.pdf',
        ]);

        // Verify file was stored
        $path = $response->json('path');
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function upload_validates_file_is_required(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->postJson(route('conversations.upload'), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function upload_validates_file_size_limit(): void
    {
        // Arrange
        Storage::fake('public');
        $largeFile = UploadedFile::fake()->create('large-document.pdf', 15000); // 15MB (over 10MB limit)

        // Act
        $response = $this->actingAs($this->user)
            ->postJson(route('conversations.upload'), [
                'file' => $largeFile,
            ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function unauthenticated_user_cannot_upload_files(): void
    {
        // Arrange
        Storage::fake('public');
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        // Act
        $response = $this->postJson(route('conversations.upload'), [
            'file' => $file,
        ]);

        // Assert
        $response->assertStatus(401);
    }
}
