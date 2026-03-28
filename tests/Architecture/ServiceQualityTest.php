<?php

declare(strict_types=1);

/**
 * Architecture Tests – Service Layer Quality Enforcement
 *
 * Rules that guard the Services layer against common architectural drift:
 * - Services must not reference Http layer (wrong dependency direction)
 * - Module services must not import controllers
 * - Services must not extend controllers
 */
arch('services do not depend on Http layer')
    ->expect('App\Services')
    ->not->toUse([
        'Illuminate\Http\Request',
        'Illuminate\Http\Response',
        'Illuminate\Http\JsonResponse',
    ])
    ->ignoring([
        'App\Services\MetricsService',       // Uses request() for security context
        'App\Services\RateLimiterService',   // Rate limiter needs request context
    ]);

arch('module services do not import controllers')
    ->expect('Modules\*\Services')
    ->not->toUse('Modules\*\Http\Controllers');

arch('services do not extend controllers')
    ->expect('App\Services')
    ->not->toExtend('Illuminate\Routing\Controller');

arch('module services do not extend controllers')
    ->expect('Modules\*\Services')
    ->not->toExtend('Illuminate\Routing\Controller');

/**
 * Service finality ratchet guard.
 *
 * Tracks the number of non-final service classes to prevent regression.
 * The baseline should decrease over time as services are migrated to final.
 *
 * Phase-7 Target: All new services MUST be final.
 * Baseline expires: 2026-06-30 (reduce by 20% each quarter).
 */
test('non-final service class count does not regress', function () {
    $root = dirname(__DIR__, 2);
    $appServices = glob($root.'/app/Services/*.php');
    $moduleServices = glob($root.'/Modules/*/Services/*.php');
    $allServices = array_merge($appServices ?: [], $moduleServices ?: []);

    $nonFinalCount = 0;
    foreach ($allServices as $file) {
        $content = file_get_contents($file);
        if ($content === false) {
            continue;
        }
        // Skip abstract/interface files
        if (str_contains($content, 'abstract class') || str_contains($content, 'interface ')) {
            continue;
        }
        if (! str_contains($content, 'final class')) {
            $nonFinalCount++;
        }
    }

    // Baseline: 80 non-final services as of 2026-03-27.
    // This number must NOT increase. Reduce to 60 by 2026-06-30.
    $baselineMax = 80;

    expect($nonFinalCount)->toBeLessThanOrEqual(
        $baselineMax,
        "Non-final service count ({$nonFinalCount}) exceeds baseline ({$baselineMax}). "
        .'New services MUST be declared final. Do not increase this baseline.'
    );
});
