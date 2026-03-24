<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ConversationController extends Controller
{
    public function index(): JsonResponse
    {
        return new JsonResponse([]);
    }
}
