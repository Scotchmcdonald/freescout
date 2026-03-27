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
 * @property array<string, mixed>|null $filters
 * @property bool $is_default
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SavedSearch extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;

    /**
     * Maximum length for search name.
     */
    public const NAME_MAX_LENGTH = 255;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
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
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include searches for a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<SavedSearch>  $query
     * @return \Illuminate\Database\Eloquent\Builder<SavedSearch>
     */
    public function scopeForUser(\Illuminate\Database\Eloquent\Builder $query, int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to order by sort order.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<SavedSearch>  $query
     * @return \Illuminate\Database\Eloquent\Builder<SavedSearch>
     */
    public function scopeOrdered(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Get the default saved search for a user.
     */
    public static function getDefaultForUser(int $userId): ?self
    {
        return static::forUser($userId)->where('is_default', true)->first();
    }

    /**
     * Set this search as the default for the user.
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
     */
    public function getUrl(): string
    {
        $params = ['q' => $this->query];

        if (! empty($this->filters)) {
            $params = array_merge($params, $this->filters);
        }

        return route('conversations.search', $params);
    }

    /**
     * Get the display name for the saved search.
     */
    public function getDisplayName(): string
    {
        return $this->name ?: __('Unnamed Search');
    }

    /**
     * Get filters as a string summary.
     */
    public function getFiltersSummary(): string
    {
        if (empty($this->filters)) {
            return '';
        }

        $parts = [];

        if (! empty($this->filters['mailbox'])) {
            $mailboxId = $this->filters['mailbox'];
            $parts[] = __('Mailbox: :id', ['id' => is_scalar($mailboxId) ? $mailboxId : '']);
        }

        if (! empty($this->filters['assigned'])) {
            $assignedId = $this->filters['assigned'];
            $parts[] = __('Assigned: :id', ['id' => is_scalar($assignedId) ? $assignedId : '']);
        }

        if (! empty($this->filters['status'])) {
            $statusVal = $this->filters['status'];
            $parts[] = __('Status: :status', ['status' => is_scalar($statusVal) ? $statusVal : '']);
        }

        if (! empty($this->filters['type'])) {
            $typeVal = $this->filters['type'];
            $parts[] = __('Type: :type', ['type' => is_scalar($typeVal) ? $typeVal : '']);
        }

        if (! empty($this->filters['date_from'])) {
            $dateFrom = $this->filters['date_from'];
            $parts[] = __('From: :date', ['date' => is_scalar($dateFrom) ? $dateFrom : '']);
        }

        if (! empty($this->filters['date_to'])) {
            $dateTo = $this->filters['date_to'];
            $parts[] = __('To: :date', ['date' => is_scalar($dateTo) ? $dateTo : '']);
        }

        return implode(', ', $parts);
    }
}
