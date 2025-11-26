<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $fillable = [
        'name',
        'title',
        'config',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'config' => 'array',
        'is_system' => 'boolean',
    ];
}
