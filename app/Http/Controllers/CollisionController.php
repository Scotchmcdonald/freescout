<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CollisionController extends Controller
{
    /**
     * Handle collision detection (who is viewing the conversation).
     */
    public function viewing(Request $request, int $id)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json([], 401);
        }

        $conversation = Conversation::find($id);
        if (! $conversation) {
            return response()->json([], 404);
        }

        // Check access
        if (! $user->isAdmin() && ! $user->mailboxes->contains($conversation->mailbox_id)) {
            return response()->json([], 403);
        }

        $key = "conversation:{$id}:viewers";
        
        // Get current viewers
        $viewers = Cache::get($key, []);
        
        // Add/Update current user
        $viewers[$user->id] = [
            'id' => $user->id,
            'name' => $user->getFullName(),
            'photo_url' => $user->photo_url, // Assuming this accessor exists
            'timestamp' => now()->timestamp,
        ];
        
        // Remove stale viewers (older than 45 seconds)
        // Frontend should poll every 15-30 seconds
        $viewers = array_filter($viewers, function ($viewer) {
            return $viewer['timestamp'] > now()->subSeconds(45)->timestamp;
        });
        
        // Save back to cache (expires in 1 minute to auto-cleanup if abandoned)
        Cache::put($key, $viewers, 60);
        
        // Return other viewers
        $others = array_filter($viewers, fn($v) => $v['id'] !== $user->id);
        
        return response()->json(array_values($others));
    }
}
