<?php

declare(strict_types=1);

namespace Modules\AppHealth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInternalAppHealthAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('apphealth.security.internal_token', '');
        $expected = is_string($configuredToken) ? $configuredToken : '';

        if ($expected === '') {
            $allowedInTesting = app()->runningUnitTests() && (bool) config('apphealth.security.allow_without_token_in_testing', false);

            if (! $allowedInTesting) {
                abort(403, 'AppHealth internal token is not configured.');
            }

            return $next($request);
        }

        $configuredHeader = config('apphealth.security.header', 'X-AppHealth-Token');
        $headerName = is_string($configuredHeader) ? $configuredHeader : 'X-AppHealth-Token';
        $provided = (string) ($request->header($headerName) ?? '');

        if ($provided === '') {
            $provided = (string) $request->bearerToken();
        }

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            abort(403, 'Invalid AppHealth token.');
        }

        return $next($request);
    }
}
