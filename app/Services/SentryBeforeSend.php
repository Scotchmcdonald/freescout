<?php

declare(strict_types=1);

namespace App\Services;

use Sentry\Event;

/**
 * Sentry before_send callback.
 * Uses a static method so it can be referenced as [Class::class, 'handle'] —
 * an array of two strings is serializable by var_export (config:cache)
 * and satisfies is_callable() (Sentry's OptionsResolver).
 */
class SentryBeforeSend
{
    public static function handle(Event $event): Event
    {
        // Scrub sensitive data from breadcrumbs
        $breadcrumbs = $event->getBreadcrumbs();
        foreach ($breadcrumbs as $breadcrumb) {
            $data = $breadcrumb->getMetadata();

            if (isset($data['bindings'])) {
                /** @var array<mixed, mixed> $bindings */
                $bindings = $data['bindings'];
                foreach ($bindings as $key => $value) {
                    if (preg_match('/password|token|secret|api_key/i', (string) $key)) {
                        $bindings[$key] = '[REDACTED]';
                    }
                }
                $data['bindings'] = $bindings;
                $breadcrumb->setMetadata($data);
            }
        }

        return $event;
    }
}
