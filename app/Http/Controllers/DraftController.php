<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTransferObjects\DraftData;
use App\Http\Requests\SaveDraftRequest;
use App\Misc\Draft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DraftController extends Controller
{
    /**
     * Save a draft.
     */
    public function save(SaveDraftRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User)) {
            return response()->json([
                'status' => 'error',
                'message' => __('Unauthorized'),
            ], 401);
        }

        $dto = DraftData::fromSaveRequest($request, $user->id);

        $thread = Draft::save($dto->toArray(), $user);

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
    public function discard(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        if (!($user instanceof \App\Models\User)) {
            return response()->json([
                'status' => 'error',
                'message' => __('Unauthorized'),
            ], 401);
        }
        
        $threadId = $request->input('thread_id');
        $threadIdInt = is_numeric($threadId) ? intval($threadId) : 0;
        
        if (Draft::discard($threadIdInt, $user)) {
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
