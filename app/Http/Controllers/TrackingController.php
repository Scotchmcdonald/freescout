<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Thread;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function pixel(Request $request, int $id): \Illuminate\Http\Response
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $thread = Thread::find($id);
        if ($thread && is_null($thread->opened_at)) {
            $thread->update(['opened_at' => now()]);
        }

        // Return 1x1 transparent GIF
        return response(base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw=='))
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }
}
