<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    /**
     * Display a listing of conversations.
     */
    public function index(Request $request, Mailbox $mailbox): View|ViewFactory
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access to mailbox
        if (! $user->mailboxes->contains($mailbox->id)) {
            abort(403);
        }

        $conversations = Conversation::with(['customer', 'user', 'folder', 'mailbox'])
            ->where('mailbox_id', $mailbox->id)
            ->where('state', 2) // Published
            ->orderBy('last_reply_at', 'desc')
            ->paginate(50);

        return view('conversations.index', compact('conversations', 'mailbox'));
    }

    /**
     * View a conversation.
     */
    public function show(Request $request, Conversation $conversation): View|RedirectResponse|ViewFactory
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access - user must be attached to the mailbox
        if (! $user->mailboxes->contains($conversation->mailbox_id)) {
            abort(403);
        }

        // Mark as read
        $user->unreadNotifications()
            ->where('data', 'like', '%"conversation_id":'.$conversation->id.'%')
            ->update(['read_at' => now()]);

        // Load relationships
        $conversation->load([
            'mailbox',
            'customer',
            'user',
            'folder',
            'threads' => function ($query) {
                $query->where('state', 2) // Published
                    ->orderBy('created_at', 'asc');
            },
            'threads.user',
            'threads.customer',
            'threads.attachments',
        ]);

        // Get folders for sidebar
        $folders = $conversation->mailbox->folders()
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->get();

        return view('conversations.show', compact('conversation', 'folders'));
    }

    /**
     * Create a new conversation.
     */
    public function create(Request $request, Mailbox $mailbox): View|ViewFactory
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access
        if (! $user->isAdmin() && ! $user->mailboxes->contains($mailbox->id)) {
            abort(403);
        }

        // Get folders
        $folders = $mailbox->folders()
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->get();

        return view('conversations.create', compact('mailbox', 'folders'));
    }

    /**
     * Store a new conversation.
     */
    public function store(Request $request, Mailbox $mailbox): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access
        if (! $user->isAdmin() && ! $user->mailboxes->contains($mailbox->id)) {
            abort(403);
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:998',
            'body' => 'required|string',
            'to' => 'required|array|min:1',
            'to.*' => 'email',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_email' => 'nullable|email',
            'customer_first_name' => 'nullable|string|max:50',
            'customer_last_name' => 'nullable|string|max:50',
            'status' => 'nullable|integer|in:1,2,3',
            'assign_to' => 'nullable|exists:users,id',
        ]);

        DB::beginTransaction();

        try {
            // Find or create customer
            if (! empty($validated['customer_id'])) {
                /** @var \App\Models\Customer $customer */
                $customer = Customer::findOrFail($validated['customer_id']);
                $customerEmail = $customer->getMainEmail() ?? $validated['customer_email'];
            } else {
                // Create or find customer by email using the Customer::create() method
                $customerEmail = $validated['customer_email'];
                $customer = Customer::create($customerEmail, [
                    'first_name' => $validated['customer_first_name'] ?? '',
                    'last_name' => $validated['customer_last_name'] ?? '',
                ]);

                if (! $customer) {
                    throw new \Exception('Failed to create customer with email: '.$customerEmail);
                }
            }

            // Get next conversation number
            $maxNumber = $mailbox->conversations()->max('number');
            $number = (is_int($maxNumber) ? $maxNumber : 0) + 1;

            // Get default folder
            $folder = $mailbox->folders()->where('type', 1)->first(); // Inbox type

            if (! $folder) {
                throw new \Exception('Inbox folder not found for mailbox: '.$mailbox->name);
            }

            // Create conversation
            /** @var \App\Models\Conversation $conversation */
            $conversation = Conversation::create([
                'mailbox_id' => $mailbox->id,
                'customer_id' => $customer->id,
                'folder_id' => $folder->id,
                'user_id' => $validated['assign_to'] ?? null,
                'number' => $number,
                'subject' => $validated['subject'],
                'type' => 1, // Email
                'status' => $validated['status'] ?? 1,
                'state' => 2, // Published
                'source_via' => 1, // User
                'source_type' => 2, // Web
                'customer_email' => $customerEmail,
                'preview' => mb_substr(strip_tags($validated['body']), 0, 255),
                'created_by_user_id' => $user->id,
                'last_reply_at' => now(),
            ]);

            // Create first thread
            Thread::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'type' => 1, // Message
                'status' => 1, // Active
                'state' => 2, // Published
                'source_via' => 1, // User
                'source_type' => 2, // Web
                'body' => $validated['body'],
                'from' => $mailbox->email,
                'to' => json_encode([$customerEmail]),
                'first' => true,
            ]);

            // Update conversation thread count
            $conversation->update(['threads_count' => 1]);

            DB::commit();

            return redirect()
                ->route('conversations.show', $conversation)
                ->with('success', 'Conversation created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create conversation: '.$e->getMessage()]);
        }
    }

    /**
     * Update conversation details (status, assignee, folder, etc).
     */
    public function update(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access
        if (! $user->isAdmin() && ! $user->mailboxes->contains($conversation->mailbox_id)) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'nullable|integer|in:1,2,3',
            'user_id' => 'nullable|integer|exists:users,id',
            'folder_id' => 'nullable|integer|exists:folders,id',
        ]);

        $conversation->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Conversation updated successfully.',
            ]);
        }

        return redirect()
            ->route('conversations.show', $conversation)
            ->with('success', 'Conversation updated successfully.');
    }

    /**
     * Reply to a conversation.
     */
    public function reply(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access
        if (! $user->isAdmin() && ! $user->mailboxes->contains($conversation->mailbox_id)) {
            abort(403);
        }

        $validated = $request->validate([
            'body' => 'required|string',
            'type' => 'nullable|integer|in:1,2', // 1=reply, 2=note (default 1)
            'status' => 'nullable|integer|in:1,2,3',
        ]);

        DB::beginTransaction();

        try {
            // Create thread
            /** @var \App\Models\Thread $thread */
            $thread = Thread::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'type' => $validated['type'] ?? 1,
                'status' => 1, // Active
                'state' => 2, // Published
                'source_via' => 1, // User
                'source_type' => 2, // Web
                'body' => $validated['body'],
                'from' => $conversation->mailbox->email,
                'to' => json_encode([$conversation->customer_email]),
                'created_by_user_id' => $user->id,
            ]);

            // Update conversation
            $updateData = [
                'threads_count' => $conversation->threads_count + 1,
                'last_reply_at' => now(),
                'status' => $validated['status'] ?? $conversation->status,
            ];

            if (is_null($conversation->user_id)) {
                $updateData['user_id'] = $user->id;
            }

            $conversation->update($updateData);

            DB::commit();

            // Send email notification if it's a reply (not a note)
            $type = $validated['type'] ?? 1;
            if ($type == 1) {
                // \App\Jobs\SendConversationReply::dispatch(
                //     $conversation,
                //     $thread,
                //     $conversation->customer_email
                // );
            }

            // Return appropriate response based on request expectation
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'thread' => $thread->load('user'),
                ]);
            }

            return redirect()
                ->route('conversations.show', $conversation)
                ->with('success', 'Reply added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Search conversations.
     */
    public function search(Request $request): View|ViewFactory
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $searchQuery = (string) $request->input('q', '');

        /** @var \Illuminate\Database\Eloquent\Builder<\App\Models\Conversation> $queryBuilder */
        $queryBuilder = Conversation::query()
            ->whereIn(
                'mailbox_id',
                $user->isAdmin()
                ? Mailbox::pluck('id')
                : $user->mailboxes->pluck('id')
            )
            ->where('state', 2) // Published
            ->where(function ($q) use ($searchQuery) {
                $q->where('subject', 'like', "%{$searchQuery}%")
                    ->orWhere('preview', 'like', "%{$searchQuery}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($searchQuery) {
                        $customerQuery->where('first_name', 'like', "%{$searchQuery}%")
                            ->orWhere('last_name', 'like', "%{$searchQuery}%");
                    });
            });

        $conversations = $queryBuilder
            ->with(['mailbox', 'customer', 'user', 'folder'])
            ->orderBy('last_reply_at', 'desc')
            ->paginate(50);

        return view('conversations.search', ['conversations' => $conversations, 'query' => $searchQuery]);
    }

    /**
     * Update conversation status/assignee via AJAX.
     */
    public function ajax(Request $request): JsonResponse
    {
        $action = $request->input('action');
        $conversationId = $request->input('conversation_id');

        if (! $conversationId) {
            return response()->json(['success' => false, 'message' => 'Conversation ID required'], 400);
        }

        /** @var \App\Models\Conversation $conversation */
        $conversation = Conversation::findOrFail($conversationId);
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access
        if (! $user->isAdmin() && ! $user->mailboxes->contains($conversation->mailbox_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        switch ($action) {
            case 'change_status':
                $conversation->update(['status' => $request->input('status')]);

                return response()->json(['success' => true]);

            case 'change_user':
                $conversation->update(['user_id' => $request->input('user_id')]);

                return response()->json(['success' => true]);

            case 'change_folder':
                $conversation->update(['folder_id' => $request->input('folder_id')]);

                return response()->json(['success' => true]);

            case 'delete':
                $conversation->update(['state' => 3]); // Deleted state

                return response()->json(['success' => true]);

            default:
                return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }

    /**
     * Upload attachment via AJAX.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');

        // Ensure file is an UploadedFile instance
        if (! $file instanceof \Illuminate\Http\UploadedFile) {
            return response()->json(['success' => false, 'message' => 'Invalid file upload'], 400);
        }

        $path = $file->store('attachments', 'public');

        return response()->json([
            'success' => true,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'size' => $file->getSize(),
        ]);
    }

    /**
     * Clone an existing conversation from a thread.
     */
    public function clone(Request $request, Mailbox $mailbox, Thread $thread): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access to mailbox
        $this->authorize('view', $mailbox);

        // Get original conversation
        $originalConversation = $thread->conversation;
        $this->authorize('view', $originalConversation);

        // Create new conversation with same properties
        $conversation = new Conversation;
        $conversation->type = $originalConversation->type;
        $conversation->subject = $originalConversation->subject;
        $conversation->mailbox_id = $originalConversation->mailbox_id;
        $conversation->source_via = $originalConversation->source_via;
        $conversation->source_type = $originalConversation->source_type;
        $conversation->customer_id = $originalConversation->customer_id;
        $conversation->customer_email = $originalConversation->customer_email;
        $conversation->status = 1; // Active
        $conversation->state = 2; // Published
        $conversation->cc = $thread->cc;
        $conversation->bcc = $thread->bcc;
        $conversation->user_id = $originalConversation->user_id;
        $conversation->save();

        // Update folder
        $conversation->updateFolder();

        // Create cloned thread
        $newThread = new Thread;
        $newThread->conversation_id = $conversation->id;
        $newThread->user_id = $thread->user_id;
        $newThread->type = $thread->type;
        $newThread->status = $conversation->status;
        $newThread->state = $conversation->state;
        $newThread->body = $thread->body;
        $newThread->headers = $thread->headers;
        $newThread->from = $thread->from;
        $newThread->to = $thread->to;
        $newThread->cc = $thread->cc;
        $newThread->bcc = $thread->bcc;
        $newThread->has_attachments = $thread->has_attachments;
        $newThread->message_id = 'clone'.crc32(microtime()).'-'.$thread->message_id;
        $newThread->source_via = $thread->source_via;
        $newThread->source_type = $thread->source_type;
        $newThread->customer_id = $thread->customer_id;
        $newThread->created_by_customer_id = $thread->created_by_customer_id;
        $newThread->save();

        // Clone attachments if any
        if ($thread->has_attachments) {
            foreach ($thread->attachments as $attachment) {
                $newAttachment = $attachment->replicate();
                $newAttachment->thread_id = $newThread->id;
                $newAttachment->save();
            }
        }

        return redirect()->route('conversations.show', $conversation)
            ->with('success', 'Conversation cloned successfully.');
    }

    /**
     * Delete a conversation.
     */
    public function destroy(Request $request, Conversation $conversation): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access - user must be attached to the mailbox
        if (! $user->mailboxes->contains($conversation->mailbox_id)) {
            abort(403, 'Unauthorized to delete this conversation');
        }

        // Store mailbox_id before deletion for redirect
        $mailboxId = $conversation->mailbox_id;

        // Soft delete the conversation
        $conversation->delete();

        return redirect()->route('mailboxes.view', $mailboxId)
            ->with('success', 'Conversation deleted successfully.');
    }

    /**
     * Load AJAX HTML partials (for modals, dropdowns, etc.).
     */
    public function ajaxHtml(Request $request): View|ViewFactory
    {
        $action = $request->input('action');
        $conversationId = $request->input('conversation_id');
        $threadId = $request->input('thread_id');

        $conversation = $conversationId ? Conversation::find($conversationId) : null;
        $thread = $threadId ? Thread::find($threadId) : null;

        // Return the appropriate partial based on action
        $viewPath = "conversations.ajax_html.{$action}";
        
        if (view()->exists($viewPath)) {
            return view($viewPath, compact('conversation', 'thread'));
        }

        abort(404, 'View not found');
    }

    /**
     * Change the customer for a conversation.
     */
    public function changeCustomer(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access
        if (! $user->mailboxes->contains($conversation->mailbox_id)) {
            abort(403);
        }

        $validated = $request->validate([
            'customer_id' => 'nullable|integer|exists:customers,id',
            'new_customer_email' => 'nullable|email|required_without:customer_id',
            'new_customer_first_name' => 'nullable|string',
            'new_customer_last_name' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $customerId = $validated['customer_id'];

            // Create new customer if needed
            if (! $customerId && ! empty($validated['new_customer_email'])) {
                /** @var \App\Models\Customer $newCustomer */
                $newCustomer = Customer::create([
                    'first_name' => $validated['new_customer_first_name'] ?? '',
                    'last_name' => $validated['new_customer_last_name'] ?? '',
                    'email' => $validated['new_customer_email'],
                ]);
                $customerId = $newCustomer->id;
            }

            if ($customerId) {
                $customer = Customer::findOrFail($customerId);
                $conversation->update([
                    'customer_id' => $customerId,
                    'customer_email' => $customer->email,
                ]);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect()
                ->route('conversations.show', $conversation)
                ->with('success', 'Customer changed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return back()->withErrors(['error' => 'Failed to change customer: '.$e->getMessage()]);
        }
    }

    /**
     * Merge conversations.
     */
    public function merge(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access
        if (! $user->mailboxes->contains($conversation->mailbox_id)) {
            abort(403);
        }

        $validated = $request->validate([
            'target_conversation_id' => 'required|integer|exists:conversations,id',
            'keep_threads' => 'nullable|boolean',
            'update_customer' => 'nullable|boolean',
        ]);

        $targetConversation = Conversation::findOrFail($validated['target_conversation_id']);

        // Prevent merging into self
        if ($conversation->id === $targetConversation->id) {
            return back()->withErrors(['error' => 'Cannot merge a conversation into itself']);
        }

        DB::beginTransaction();

        try {
            // Move threads if requested
            if ($validated['keep_threads'] ?? true) {
                Thread::where('conversation_id', $conversation->id)
                    ->update(['conversation_id' => $targetConversation->id]);
                
                // Update thread count
                $targetConversation->increment('threads_count', $conversation->threads_count);
            }

            // Update customer if requested
            if ($validated['update_customer'] ?? false) {
                $conversation->update([
                    'customer_id' => $targetConversation->customer_id,
                    'customer_email' => $targetConversation->customer_email,
                ]);
            }

            // Mark source conversation as merged/deleted
            $conversation->update(['state' => 3]); // Deleted state

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect()
                ->route('conversations.show', $targetConversation)
                ->with('success', 'Conversations merged successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return back()->withErrors(['error' => 'Failed to merge conversations: '.$e->getMessage()]);
        }
    }

    /**
     * Move conversation to different mailbox.
     */
    public function move(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access
        if (! $user->mailboxes->contains($conversation->mailbox_id)) {
            abort(403);
        }

        $validated = $request->validate([
            'mailbox_id' => 'required|integer|exists:mailboxes,id',
        ]);

        // Check user has access to target mailbox
        if (! $user->mailboxes->contains($validated['mailbox_id'])) {
            abort(403, 'You do not have access to the target mailbox');
        }

        $conversation->update(['mailbox_id' => $validated['mailbox_id']]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('conversations.show', $conversation)
            ->with('success', 'Conversation moved successfully.');
    }

    /**
     * Update a specific thread.
     */
    public function updateThread(Request $request, Conversation $conversation, Thread $thread): RedirectResponse|JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access
        if (! $user->mailboxes->contains($conversation->mailbox_id)) {
            abort(403);
        }

        // Verify thread belongs to conversation
        if ($thread->conversation_id !== $conversation->id) {
            abort(404, 'Thread not found in this conversation');
        }

        $validated = $request->validate([
            'body' => 'required|string',
        ]);

        $thread->update([
            'body' => $validated['body'],
            'edited_by_user_id' => $user->id,
            'edited_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('conversations.show', $conversation)
            ->with('success', 'Thread updated successfully.');
    }

    /**
     * Update conversation settings (tags, priority, custom fields).
     */
    public function updateSettings(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access
        if (! $user->mailboxes->contains($conversation->mailbox_id)) {
            abort(403);
        }

        $validated = $request->validate([
            'tags' => 'nullable|string',
            'priority' => 'nullable|string|in:normal,high,urgent',
            'custom_field_1' => 'nullable|string',
            'custom_field_2' => 'nullable|string',
            'internal_notes' => 'nullable|string',
        ]);

        // Parse tags
        $tags = [];
        if (! empty($validated['tags'])) {
            $tags = array_map('trim', explode(',', $validated['tags']));
        }

        // Update meta field
        $meta = $conversation->meta ?? [];
        $meta['tags'] = $tags;
        $meta['priority'] = $validated['priority'] ?? 'normal';
        $meta['custom_field_1'] = $validated['custom_field_1'] ?? '';
        $meta['custom_field_2'] = $validated['custom_field_2'] ?? '';
        $meta['internal_notes'] = $validated['internal_notes'] ?? '';

        $conversation->update(['meta' => $meta]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('conversations.show', $conversation)
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Display chats view.
     */
    public function chats(Request $request): View|ViewFactory
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Get conversations in chat mode
        $conversations = Conversation::with(['customer', 'threads'])
            ->whereHas('mailbox', function ($query) use ($user) {
                $query->whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            })
            ->where('type', 1) // Chat type
            ->orderBy('last_reply_at', 'desc')
            ->limit(50)
            ->get();

        $activeConversation = null;
        if ($request->has('id')) {
            $activeConversation = Conversation::with(['customer', 'threads.user', 'threads.customer'])
                ->find($request->input('id'));
        }

        return view('conversations.chats', compact('conversations', 'activeConversation'));
    }
}
