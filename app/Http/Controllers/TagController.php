<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TagController extends Controller
{
    public function ajaxSearch(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json([]);
    }
}
