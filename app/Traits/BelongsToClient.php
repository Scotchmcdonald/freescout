<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Crm\Models\ClientUser;

/**
 * Client Scope Trait
 * 
 * Automatically scopes queries to the authenticated client user's client.
 * Apply this trait to models that belong to a client and need isolation.
 * 
 * Usage:
 * - Add `use BelongsToClient;` to your model
 * - Ensure the model has a `client_id` column
 * 
 * The scope is automatically applied when:
 * - A client user is authenticated via the 'client' guard
 * 
 * Admin users (authenticated via 'web' guard) bypass this scope.
 */
trait BelongsToClient
{
    /**
     * Boot the trait
     */
    public static function bootBelongsToClient(): void
    {
        static::addGlobalScope('client', function (Builder $builder) {
            // Only apply scope when client guard is active
            if (Auth::guard('client')->check()) {
                $clientUser = Auth::guard('client')->user();
                
                if ($clientUser instanceof ClientUser && $clientUser->client_id) {
                    $builder->where($builder->getModel()->getTable() . '.client_id', $clientUser->client_id);
                }
            }
        });

        // Automatically set client_id when creating records
        static::creating(function ($model) {
            if (Auth::guard('client')->check() && empty($model->client_id)) {
                $clientUser = Auth::guard('client')->user();
                
                if ($clientUser instanceof ClientUser) {
                    $model->client_id = $clientUser->client_id;
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
        if (Auth::guard('client')->check()) {
            $clientUser = Auth::guard('client')->user();
            
            if ($clientUser instanceof ClientUser) {
                return $query->where($this->getTable() . '.client_id', $clientUser->client_id);
            }
        }

        // If no client is authenticated, return empty result set
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
        if (!Auth::guard('client')->check()) {
            return false;
        }

        $clientUser = Auth::guard('client')->user();
        
        return $clientUser instanceof ClientUser 
            && $this->client_id === $clientUser->client_id;
    }
}
