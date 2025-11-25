<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SavedSearch Model
 *
 * Stores user-saved search queries for quick access.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $query
 * @property array|null $filters
 * @property bool $is_default
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SavedSearch extends Model
{
    use HasFactory;

    /**
     * Maximum length for search name.
     */
    public const NAME_MAX_LENGTH = 255;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'query',
        'filters',
        'is_default',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'filters' => 'array',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the user that owns the saved search.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include searches for a specific user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to order by sort order.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Get the default saved search for a user.
     *
     * @param int $userId
     * @return self|null
     */
    public static function getDefaultForUser(int $userId): ?self
    {
        return static::forUser($userId)->where('is_default', true)->first();
    }

    /**
     * Set this search as the default for the user.
     *
     * @return void
     */
    public function setAsDefault(): void
    {
        // Remove default flag from other searches
        static::forUser($this->user_id)->where('id', '!=', $this->id)->update(['is_default' => false]);

        // Set this as default
        $this->is_default = true;
        $this->save();
    }

    /**
     * Build the URL for this saved search.
     *
     * @return string
     */
    public function getUrl(): string
    {
        $params = ['q' => $this->query];

        if (!empty($this->filters)) {
            $params = array_merge($params, $this->filters);
        }

        return route('conversations.search', $params);
    }

    /**
     * Get the display name for the saved search.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->name ?: __('Unnamed Search');
    }

    /**
     * Get filters as a string summary.
     *
     * @return string
     */
    public function getFiltersSummary(): string
    {
        if (empty($this->filters)) {
            return '';
        }

        $parts = [];

        if (!empty($this->filters['mailbox'])) {
            $parts[] = __('Mailbox: :id', ['id' => $this->filters['mailbox']]);
        }

        if (!empty($this->filters['assigned'])) {
            $parts[] = __('Assigned: :id', ['id' => $this->filters['assigned']]);
        }

        if (!empty($this->filters['status'])) {
            $parts[] = __('Status: :status', ['status' => $this->filters['status']]);
        }

        if (!empty($this->filters['type'])) {
            $parts[] = __('Type: :type', ['type' => $this->filters['type']]);
        }

        if (!empty($this->filters['date_from'])) {
            $parts[] = __('From: :date', ['date' => $this->filters['date_from']]);
        }

        if (!empty($this->filters['date_to'])) {
            $parts[] = __('To: :date', ['date' => $this->filters['date_to']]);
        }

        return implode(', ', $parts);
    }
}
