<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $folder_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ConversationFolder extends Pivot
{
    protected $table = 'conversation_folder';

    protected $fillable = [
        'conversation_id',
        'folder_id',
    ];

    public $timestamps = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conversation_id' => 'integer',
            'folder_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
