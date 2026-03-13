<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (! $user instanceof \App\Models\User) {
            abort(403);
        }

        // MSP Admin bypass
        if ($user->role === User::ROLE_ADMIN) {
            return $next($request);
        }

        // Get company from route parameter
        $company = $request->route('company');

        if (! $company) {
            return $next($request);
        }

        $companyId = $company instanceof \Modules\Crm\Models\Company ? $company->id : (int) $company;

        if (! $user->hasCompanyAccess($companyId)) {
            abort(403, 'Unauthorized access to this company.');
        }

        return $next($request);
    }
}
