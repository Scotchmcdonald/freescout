<?php

namespace App\Services;

use Sentry\Event;

/**
 * Invokable Sentry before_send callback.
 * Must be a class (not a closure) to support php artisan config:cache.
 */
class SentryBeforeSend
{
    public function __invoke(Event $event): Event
    {
        // Scrub sensitive data from breadcrumbs
        $breadcrumbs = $event->getBreadcrumbs();
        foreach ($breadcrumbs as $breadcrumb) {
            $data = $breadcrumb->getMetadata();

            // Remove password fields from query bindings
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
