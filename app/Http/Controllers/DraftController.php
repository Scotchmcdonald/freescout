<?php

namespace App\Http\Controllers;

use App\Misc\Draft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DraftController extends Controller
{
    /**
     * Save a draft.
     */
    public function save(Request $request)
    {
        $user = Auth::user();
        $data = $request->all();
        
        $thread = Draft::save($data, $user);
        
        if ($thread) {
            return response()->json([
                'status' => 'success',
                'thread_id' => $thread->id,
                'message' => __('Draft saved'),
            ]);
        }
        
        return response()->json([
            'status' => 'error',
            'message' => __('Failed to save draft'),
        ], 500);
    }
    
    /**
     * Discard a draft.
     */
    public function discard(Request $request)
    {
        $user = Auth::user();
        $threadId = $request->input('thread_id');
        
        if (Draft::discard($threadId, $user)) {
            return response()->json([
                'status' => 'success',
                'message' => __('Draft discarded'),
            ]);
        }
        
        return response()->json([
            'status' => 'error',
            'message' => __('Failed to discard draft'),
        ], 500);
    }
}
