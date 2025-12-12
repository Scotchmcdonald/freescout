<?php

namespace App\Misc;

use App\Models\Conversation;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Draft
{
    /**
     * Save a draft.
     * 
     * @param array<string, mixed> $data
     */
    public static function save(array $data, User $user): ?Thread
    {
        $conversationId = $data['conversation_id'] ?? null;
        $threadId = $data['thread_id'] ?? null;
        $body = $data['body'] ?? '';
        $to = $data['to'] ?? [];
        $cc = $data['cc'] ?? [];
        $bcc = $data['bcc'] ?? [];
        
        // If thread_id is provided, update existing draft
        if ($threadId) {
            /** @var \App\Models\Thread|null $thread */
            $thread = Thread::find($threadId);
            if ($thread && $thread->state == Thread::STATE_DRAFT && $thread->created_by_user_id == $user->id) {
                $thread->update([
                    'body' => $body,
                    'to' => $to,
                    'cc' => $cc,
                    'bcc' => $bcc,
                    'edited_at' => now(),
                ]);
                return $thread;
            }
        }
        
        // Create new draft thread
        if ($conversationId) {
            $conversation = Conversation::find($conversationId);
            if (!$conversation) return null;
            
            // Check if user already has a draft for this conversation
            /** @var \App\Models\Thread|null $existingDraft */
            $existingDraft = Thread::where('conversation_id', $conversationId)
                ->where('created_by_user_id', $user->id)
                ->where('state', Thread::STATE_DRAFT)
                ->first();
                
            if ($existingDraft) {
                $existingDraft->update([
                    'body' => $body,
                    'to' => $to,
                    'cc' => $cc,
                    'bcc' => $bcc,
                    'edited_at' => now(),
                ]);
                return $existingDraft;
            }
            
            /** @var \App\Models\Thread $thread */
            $thread = Thread::create([
                'conversation_id' => $conversationId,
                'created_by_user_id' => $user->id,
                'user_id' => $user->id, // Add user_id as well
                'type' => Thread::TYPE_DRAFT, // Change to TYPE_DRAFT
                'state' => Thread::STATE_DRAFT,
                'source_via' => 1, // User
                'source_type' => 2, // Web
                'body' => $body,
                'to' => $to,
                'cc' => $cc,
                'bcc' => $bcc,
            ]);
            
            return $thread;
        }
        
        return null;
    }
    
    /**
     * Discard a draft.
     */
    public static function discard(int $threadId, User $user): bool
    {
        /** @var \App\Models\Thread|null $thread */
        $thread = Thread::find($threadId);
        if ($thread && $thread->state == Thread::STATE_DRAFT && $thread->created_by_user_id == $user->id) {
            $thread->forceDelete();
            return true;
        }
        return false;
    }
}
