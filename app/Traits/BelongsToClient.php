<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/**
 * Client Scope Trait
 */
trait BelongsToClient
{
    /**
     * Boot the trait
     */
    public static function bootBelongsToClient(): void
    {
        static::addGlobalScope('client', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                if ($user->isClient() && $user->client_id) {
                    $builder->where($builder->getModel()->getTable() . '.client_id', $user->client_id);
                }
            }
        });

        // Automatically set client_id when creating records
        static::creating(function ($model) {
            if (Auth::check() && empty($model->client_id)) {
                $user = Auth::user();
                if ($user->isClient() && $user->client_id) {
                    $model->client_id = $user->client_id;
                }
            }
        });
    }

    /**
     * Scope query to a specific client
     */
    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where($this->getTable() . '.client_id', $clientId);
    }

    /**
     * Scope query to the current authenticated client
     */
    public function scopeForCurrentClient(Builder $query): Builder
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isClient() && $user->client_id) {
                return $query->where($this->getTable() . '.client_id', $user->client_id);
            }
        }
        return $query->whereRaw('1 = 0');
    }

    /**
     * Remove the client scope for admin operations
     */
    public function scopeWithoutClientScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('client');
    }

    /**
     * Check if model belongs to the given client
     */
    public function belongsToClient(int $clientId): bool
    {
        return $this->client_id === $clientId;
    }

    /**
     * Check if model belongs to the authenticated client user
     */
    public function belongsToAuthenticatedClient(): bool
    {
        if (!Auth::check()) {
            return false;
        }
        $user = Auth::user();
        return $user->isClient() && $this->client_id === $user->client_id;
    }
}
