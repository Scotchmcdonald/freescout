<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Abstract base for the entitlement engine.
 *
 * The concrete implementation lives in the PIB module.
 * The AppServiceProvider registers that concrete class under this abstract name
 * so application-layer code can depend on App\Services\EntitlementEngine without
 * a hard dependency on the module class name here.
 */
abstract class EntitlementEngine {}
