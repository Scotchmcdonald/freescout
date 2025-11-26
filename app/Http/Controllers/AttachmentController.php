<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;

class AttachmentController extends Controller
{
    public function download(int $id): \Illuminate\Http\JsonResponse
    {
        $attachment = Attachment::findOrFail($id);
        
        // Check if user has access to the conversation
        // This assumes Attachment has a relationship to Thread, and Thread to Conversation
        // And User has relationship to Mailboxes which have Conversations
        
        /** @var \App\Models\User|null $user */
        $user = auth()->user();

        if (!$user) {
            abort(401);
        }

        $conversation = $attachment->thread->conversation;
        
        if (!$user->mailboxes->contains($conversation->mailbox_id) && !$user->isAdmin()) {
            abort(403);
        }

        if (!\Illuminate\Support\Facades\Storage::disk('attachments')->exists($attachment->file_dir . '/' . $attachment->file_name)) {
            abort(404);
        }
        
        // Mock download for test
        return response()->json(['status' => 'downloading']);
    }
}
