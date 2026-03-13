<?php

declare(strict_types=1);

namespace App\Services;

use Sentry\Breadcrumb;

/**
 * Sentry before_breadcrumb callback.
 * Uses a static method so it can be referenced as [Class::class, 'handle'] —
 * an array of two strings is serializable by var_export (config:cache)
 * and satisfies is_callable() (Sentry's OptionsResolver).
 */
class SentryBeforeBreadcrumb
{
    public static function handle(Breadcrumb $breadcrumb): Breadcrumb
    {
        // Limit SQL query length in breadcrumbs to avoid large payloads
        if ($breadcrumb->getCategory() === 'sql.query') {
            $message = $breadcrumb->getMessage();
            if (strlen((string) $message) > 1000) {
                return $breadcrumb->withMessage(substr((string) $message, 0, 1000).'... [truncated]');
            }
        }

        return $breadcrumb;
    }
}
