<?php

declare(strict_types=1);

namespace Modules\AppHealth\Contracts;

interface HealthCheckContract
{
    /**
     * @return array<string, mixed>
     */
    public function basic(): array;

    /**
     * @return array<string, mixed>
     */
    public function detailed(): array;
}
