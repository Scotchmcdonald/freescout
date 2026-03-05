<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\Crm\Models\Company;

class TechnicianScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder<\Illuminate\Database\Eloquent\Model> $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Skip for admin user (role 2) or guests
        if (!auth()->check()) {
            return;
        }

        /** @var \App\Models\User $authUser */
        $authUser = auth()->user();

        if ($authUser->role === \App\Models\User::ROLE_ADMIN) {
            return;
        }

        // Apply for Technician (role 1)
        if ($authUser->role === \App\Models\User::ROLE_USER) { 
             $user = $authUser;
             
             // Get approved companies assigned to this user
             $companyIds = $user->companies()
                 ->wherePivot('status', 'approved')
                 ->pluck('companies.id')
                 ->toArray();
             
             // Client Users Handling
             // If the user is a client (based on contact association or context), we should likely skip this scope
             // or filter by client_id instead. 
             // Assuming ROLE_USER is strictly technician, but if clients share this role:
             /** @var int|null $clientId */
             $clientId = $user->getAttribute('client_id');
             if (is_numeric($clientId) && (int) $clientId > 0) {
                 // It's a client user, let them see their own stuff
                 if ($model instanceof \Modules\Crm\Models\Client) {
                     $builder->getQuery()->where('id', $clientId);
                 } elseif (method_exists($model, 'client')) {
                     // Generic support for any model with a client() relationship (Assets, Invoices, etc)
                     $builder->getQuery()->where('client_id', $clientId);
                 }
                 return;
             }
             
             \Illuminate\Support\Facades\Log::info('TechnicianScope', [
                 'user' => $user->id,
                 'company_ids' => $companyIds,
                 'model' => get_class($model)
             ]);

             // Filter based on model type
             if ($model instanceof \Modules\Crm\Models\Client) {
                 $builder->whereIn('company_id', $companyIds);
             } elseif (method_exists($model, 'client')) {
                 // Generic support for any model with a client() relationship
                 $builder->whereHas('client', function($q) use ($companyIds) {
                     $q->whereIn('company_id', $companyIds);
                 });
             }
        }
    }
}
