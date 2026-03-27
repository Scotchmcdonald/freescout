<?php

declare(strict_types=1);

namespace App\Services;

class EnvironmentDetectorService
{
    public function runningInConsole(): bool
    {
        return app()->runningInConsole();
    }
}
