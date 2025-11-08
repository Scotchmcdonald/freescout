<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $thread_id
 * @property int|null $user_id
 * @property string|null $file_dir
 * @property string $file_name
 * @property string $mime_type
 * @property int $type
 * @property int|null $size
 * @property bool $embedded
 * 
 * @property-read \App\Models\Thread $thread
 * @property-read \App\Models\User $user
 */
class Attachment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'thread_id',
        'user_id',
        'file_dir',
        'file_name',
        'mime_type',
        'type',
        'size',
        'embedded',
    ];

    protected function casts(): array
    {
        return [
            'thread_id' => 'integer',
            'user_id' => 'integer',
            'type' => 'integer',
            'size' => 'integer',
            'embedded' => 'boolean',
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
     * Get the user that owns the attachment.
     * 
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full file path.
     */
    public function getFullPathAttribute(): string
    {
        return storage_path("app/{$this->file_dir}/{$this->file_name}");
    }

    /**
     * Get the file size in human-readable format.
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Check if attachment is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }
}
