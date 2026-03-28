<?php

declare(strict_types=1);

/**
 * Architecture Tests – Strict Types Enforcement
 *
 * Validates that all key application layers declare strict_types=1.
 * Complements the broader 'strict types' rules in ArchTest.php with
 * per-layer coverage to catch drift in specific high-risk namespaces.
 */
arch('models have strict types')
    ->expect('App\Models')
    ->toUseStrictTypes()
    ->ignoring(['App\Models\QueryBuilders']);

arch('controllers have strict types')
    ->expect('App\Http\Controllers')
    ->toUseStrictTypes();

arch('services have strict types')
    ->expect('App\Services')
    ->toUseStrictTypes();

arch('all form requests must use strict types')
    ->expect('App\Http\Requests')
    ->toUseStrictTypes();

arch('all policies must use strict types')
    ->expect('App\Policies')
    ->toUseStrictTypes();
