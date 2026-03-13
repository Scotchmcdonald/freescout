<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $thread_id
 * @property int|null $conversation_id
 * @property string $file_name
 * @property string $file_dir
 * @property int $file_size
 * @property string|null $mime_type
 * @property bool $embedded
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Thread $thread
 */
class Attachment extends Model
{
    /** @use HasFactory<\Database\Factories\AttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'conversation_id',
        'file_name',
        'file_dir',
        'file_size',
        'mime_type',
        'embedded',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'thread_id' => 'integer',
            'file_size' => 'integer',
            'embedded' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the thread that owns the attachment.
     *
     * @return BelongsTo<Thread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * Get the full file path.
     *
     * @return Attribute<string, never>
     */
    protected function fullPath(): Attribute
    {
        return Attribute::make(
            get: fn () => storage_path("app/{$this->file_dir}/{$this->file_name}")
        );
    }

    /**
     * Get the file size in human-readable format.
     *
     * @return Attribute<string, never>
     */
    protected function humanFileSize(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $bytes = $this->file_size;
                $units = ['B', 'KB', 'MB', 'GB', 'TB'];

                for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
                    $bytes /= 1024;
                }

                return round($bytes, 2).' '.$units[$i];
            }
        );
    }

    /**
     * Check if attachment is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }
}
