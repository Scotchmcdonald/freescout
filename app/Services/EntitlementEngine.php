<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Abstract base for the entitlement engine.
 *
 * The concrete implementation lives in Modules/PIB/Services/EntitlementEngineService.
 * The AppServiceProvider registers that concrete class under this abstract name
 * so application-layer code can depend on App\Services\EntitlementEngine without
 * a hard dependency on the PIB module.
 *
 * @see \Modules\PIB\Services\EntitlementEngineService
 */
abstract class EntitlementEngine {}
