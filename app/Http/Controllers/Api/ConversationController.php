<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ConversationController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        return response()->json([]);
    }
}
