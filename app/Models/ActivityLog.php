<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_log';

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uuid',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the subject (the model being logged).
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the causer (the user/model that caused the action).
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who caused this activity (convenience accessor).
     */
    public function user(): ?\App\Models\User
    {
        if ($this->causer_type === \App\Models\User::class && $this->causer instanceof \App\Models\User) {
            return $this->causer;
        }
        return null;
    }

    /**
     * Scope to filter by log name.
     */
    public function scopeInLog(\Illuminate\Database\Eloquent\Builder $query, string $logName): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('log_name', $logName);
    }

    /**
     * Scope to filter by causer.
     */
    public function scopeCausedBy(\Illuminate\Database\Eloquent\Builder $query, Model $causer): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('causer_type', get_class($causer))
            ->where('causer_id', $causer->getKey());
    }

    /**
     * Scope to filter by subject.
     */
    public function scopeForSubject(\Illuminate\Database\Eloquent\Builder $query, Model $subject): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->getKey());
    }
}
