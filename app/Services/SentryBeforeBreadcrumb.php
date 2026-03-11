<?php

namespace App\Services;

use Sentry\Breadcrumb;

/**
 * Invokable Sentry before_breadcrumb callback.
 * Must be a class (not a closure) to support php artisan config:cache.
 */
class SentryBeforeBreadcrumb
{
    public function __invoke(Breadcrumb $breadcrumb): Breadcrumb
    {
        // Limit SQL query length in breadcrumbs to avoid large payloads
        if ($breadcrumb->getCategory() === 'sql.query') {
            $message = $breadcrumb->getMessage();
            if (strlen((string) $message) > 1000) {
                $breadcrumb->setMessage(substr((string) $message, 0, 1000) . '... [truncated]');
            }
        }

        return $breadcrumb;
    }
}
