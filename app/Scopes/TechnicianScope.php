<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\Crm\Models\Company;

class TechnicianScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Skip for admin user (role 2) or guests
        if (!auth()->check() || auth()->user()->role === \App\Models\User::ROLE_ADMIN) {
            return;
        }

        // Apply for Technician (role 1)
        if (auth()->user()->role === \App\Models\User::ROLE_USER) { 
             $user = auth()->user();
             
             // Get approved companies assigned to this user
             $companyIds = $user->companies()
                 ->wherePivot('status', 'approved')
                 ->pluck('companies.id')
                 ->toArray();
             
             \Illuminate\Support\Facades\Log::info('TechnicianScope', [
                 'user' => $user->id,
                 'company_ids' => $companyIds,
                 'model' => get_class($model)
             ]);

             // Filter based on model type
             if ($model instanceof \Modules\Crm\Models\Client) {
                 $builder->whereIn('company_id', $companyIds);
             } elseif ($model instanceof \Modules\AssetManagement\Entities\Asset) {
                 $builder->whereHas('client', function($q) use ($companyIds) {
                     $q->whereIn('company_id', $companyIds);
                 });
             } elseif ($model instanceof \Modules\PIB\Models\Invoice) {
                 $builder->whereHas('client', function($q) use ($companyIds) {
                     $q->whereIn('company_id', $companyIds);
                 });
             }
        }
    }
}
