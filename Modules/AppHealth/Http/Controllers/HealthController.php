<?php

declare(strict_types=1);

namespace Modules\AppHealth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\AppHealth\Contracts\HealthCheckContract;

class HealthController extends Controller
{
    public function __construct(private readonly HealthCheckContract $healthService) {}

    public function basic(): JsonResponse
    {
        $payload = $this->healthService->basic();

        return response()->json($payload, $payload['status'] === 'ok' ? 200 : 503);
    }

    public function detailed(): JsonResponse
    {
        $payload = $this->healthService->detailed();

        return response()->json($payload, $payload['status'] === 'ok' ? 200 : 503);
    }
}
