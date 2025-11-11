<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Mailbox;
use App\Models\User;
use App\Services\ImapService;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MailboxController extends Controller
{
    /**
     * Show all mailboxes.
     */
    public function index(Request $request): View|ViewFactory
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $mailboxes = $user->isAdmin()
            ? Mailbox::with('users')->get()
            : $user->mailboxes;

        return view('mailboxes.index', compact('mailboxes'));
    }

    /**
     * Show a specific mailbox.
     */
    public function show(Request $request, Mailbox $mailbox): View|ViewFactory
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access
        if (! $user->isAdmin() && ! $user->mailboxes->contains($mailbox->id)) {
            abort(403);
        }

        // Get conversations for this mailbox
        $conversations = $mailbox->conversations()
            ->with(['customer', 'user', 'folder'])
            ->where('state', 2) // Published
            ->orderBy('last_reply_at', 'desc')
            ->paginate(50);

        // Get folders for this mailbox
        $folders = $mailbox->folders;

        return view('mailboxes.show', compact('mailbox', 'conversations', 'folders'));
    }

    /**
     * Show mailbox settings and connection testing.
     */
    public function settings(Request $request, Mailbox $mailbox): View|ViewFactory
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access (admin only for settings)
        if (! $user->isAdmin()) {
            abort(403);
        }

        return view('mailboxes.settings', compact('mailbox'));
    }

    /**
     * Store a newly created mailbox.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Mailbox::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:mailboxes,email',
            'from_name' => 'nullable|string|max:255',
            'out_method' => 'nullable|in:mail,smtp',
            'out_server' => 'nullable|string|max:255',
            'out_port' => 'nullable|integer',
            'out_username' => 'nullable|string|max:255',
            'out_password' => 'nullable|string',
            'out_encryption' => 'nullable|in:none,ssl,tls',
            'in_server' => 'nullable|string|max:255',
            'in_port' => 'nullable|integer',
            'in_username' => 'nullable|string|max:255',
            'in_password' => 'nullable|string',
            'in_protocol' => 'nullable|in:imap,pop3',
            'in_encryption' => 'nullable|in:none,ssl,tls',
        ]);

        // Encrypt passwords if provided
        if (! empty($validated['out_password'])) {
            $validated['out_password'] = encrypt($validated['out_password']);
        }
        if (! empty($validated['in_password'])) {
            $validated['in_password'] = encrypt($validated['in_password']);
        }

        if (isset($validated['from_name'])) {
            $validated['from_name_custom'] = $validated['from_name'];
            $validated['from_name'] = 3; // custom
        } else {
            $validated['from_name'] = 1; // mailbox name
        }

        $mailbox = Mailbox::create($validated);

        return redirect()->route('mailboxes.index')
            ->with('success', 'Mailbox created successfully.');
    }

    /**
     * Update the specified mailbox.
     */
    public function update(Request $request, Mailbox $mailbox): RedirectResponse
    {
        $this->authorize('update', $mailbox);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:mailboxes,email,'.$mailbox->id,
            'from_name' => 'nullable|string|max:255',
            'out_method' => 'nullable|in:mail,smtp',
            'out_server' => 'nullable|string|max:255',
            'out_port' => 'nullable|integer',
            'out_username' => 'nullable|string|max:255',
            'out_password' => 'nullable|string',
            'out_encryption' => 'nullable|in:none,ssl,tls',
            'in_server' => 'nullable|string|max:255',
            'in_port' => 'nullable|integer',
            'in_username' => 'nullable|string|max:255',
            'in_password' => 'nullable|string',
            'in_protocol' => 'nullable|in:imap,pop3',
            'in_encryption' => 'nullable|in:none,ssl,tls',
            'auto_reply_enabled' => 'nullable|boolean',
            'auto_reply_subject' => 'nullable|string|max:255',
            'auto_reply_message' => 'nullable|string',
        ]);

        // Encrypt passwords if provided and changed
        if (! empty($validated['out_password'])) {
            $validated['out_password'] = encrypt($validated['out_password']);
        } else {
            unset($validated['out_password']);
        }
        if (! empty($validated['in_password'])) {
            $validated['in_password'] = encrypt($validated['in_password']);
        } else {
            unset($validated['in_password']);
        }

        if (isset($validated['from_name'])) {
            $validated['from_name_custom'] = $validated['from_name'];
            $validated['from_name'] = 3; // custom
        }

        $mailbox->update($validated);

        return redirect()->route('mailboxes.index')
            ->with('success', 'Mailbox updated successfully.');
    }

    /**
     * Remove the specified mailbox.
     */
    public function destroy(Request $request, Mailbox $mailbox): RedirectResponse
    {
        $this->authorize('delete', $mailbox);

        $mailbox->delete();

        return redirect()->route('mailboxes.index')
            ->with('success', 'Mailbox deleted successfully.');
    }

    /**
     * Manually fetch emails for a specific mailbox.
     */
    public function fetchEmails(Request $request, Mailbox $mailbox, ImapService $imapService): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Check access (admin only)
        if (! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        try {
            $stats = $imapService->fetchEmails($mailbox);

            return response()->json([
                'success' => true,
                'message' => "Successfully fetched {$stats['fetched']} emails. Created {$stats['created']} new conversations.",
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch emails: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show incoming connection settings.
     */
    public function connectionIncoming(Request $request, Mailbox $mailbox): View|ViewFactory
    {
        $this->authorize('update', $mailbox);

        return view('mailboxes.connection_incoming', compact('mailbox'));
    }

    /**
     * Save incoming connection settings.
     */
    public function saveConnectionIncoming(Request $request, Mailbox $mailbox): RedirectResponse
    {
        $this->authorize('update', $mailbox);

        $validated = $request->validate([
            'in_protocol' => 'required|in:imap,pop3',
            'in_server' => 'required|string|max:255',
            'in_port' => 'required|integer',
            'in_encryption' => 'nullable|in:none,ssl,tls',
            'in_username' => 'required|string|max:255',
            'in_password' => 'nullable|string',
        ]);

        // Transform protocol to integer
        $validated['in_protocol'] = ($validated['in_protocol'] === 'imap') ? 1 : 2;

        // Transform encryption to integer
        $encryptionMap = ['none' => 0, 'ssl' => 1, 'tls' => 2];
        $validated['in_encryption'] = $encryptionMap[$validated['in_encryption']] ?? 0;

        if (! empty($validated['in_password'])) {
            $validated['in_password'] = encrypt($validated['in_password']);
        } else {
            unset($validated['in_password']);
        }

        $mailbox->update($validated);

        return redirect()->route('mailboxes.connection.incoming', $mailbox)
            ->with('success', 'Incoming connection settings saved.');
    }

    /**
     * Show outgoing connection settings.
     */
    public function connectionOutgoing(Request $request, Mailbox $mailbox): View|ViewFactory
    {
        $this->authorize('update', $mailbox);

        return view('mailboxes.connection_outgoing', compact('mailbox'));
    }

    /**
     * Save outgoing connection settings.
     */
    public function saveConnectionOutgoing(Request $request, Mailbox $mailbox): RedirectResponse
    {
        $this->authorize('update', $mailbox);

        $validated = $request->validate([
            'out_method' => 'required|in:mail,smtp',
            'from_name' => 'nullable|string|max:255',
            'out_server' => 'nullable|string|max:255',
            'out_port' => 'nullable|integer',
            'out_encryption' => 'nullable|in:none,ssl,tls',
            'out_username' => 'nullable|string|max:255',
            'out_password' => 'nullable|string',
        ]);

        // Transform method to integer
        $validated['out_method'] = ($validated['out_method'] === 'smtp') ? 3 : 1;

        // Transform encryption to integer
        $encryptionMap = ['none' => 0, 'ssl' => 1, 'tls' => 2];
        $validated['out_encryption'] = $encryptionMap[$validated['out_encryption']] ?? 0;

        // Handle from_name
        if (! empty($validated['from_name'])) {
            $validated['from_name_custom'] = $validated['from_name'];
            $validated['from_name'] = 3; // custom
        } else {
            $validated['from_name'] = 1; // mailbox name
        }

        if (! empty($validated['out_password'])) {
            $validated['out_password'] = encrypt($validated['out_password']);
        } else {
            unset($validated['out_password']);
        }

        $mailbox->update($validated);

        return redirect()->route('mailboxes.connection.outgoing', $mailbox)
            ->with('success', 'Outgoing connection settings saved.');
    }

    /**
     * Show mailbox permissions management page.
     */
    public function permissions(Request $request, Mailbox $mailbox): View|ViewFactory
    {
        $this->authorize('update', $mailbox);

        $users = User::where('status', User::STATUS_ACTIVE)
            ->with(['mailboxes' => fn ($query) => $query->where('mailboxes.id', $mailbox->id)])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('mailboxes.permissions', compact('mailbox', 'users'));
    }

    /**
     * Update mailbox permissions.
     */
    public function updatePermissions(Request $request, Mailbox $mailbox): RedirectResponse
    {
        $this->authorize('update', $mailbox);

        $validated = $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'nullable|in:10,20,30',
        ]);

        $syncData = [];
        foreach ($validated['permissions'] as $userId => $access) {
            if (! empty($access)) {
                $syncData[$userId] = ['access' => $access];
            }
        }

        $mailbox->users()->sync($syncData);

        return redirect()->route('mailboxes.permissions', $mailbox)
            ->with('success', 'Mailbox permissions updated successfully.');
    }

    /**
     * Show auto-reply settings page.
     */
    public function autoReply(Request $request, Mailbox $mailbox): View|ViewFactory
    {
        $this->authorize('update', $mailbox);

        return view('mailboxes.auto_reply', compact('mailbox'));
    }

    /**
     * Save auto-reply settings.
     */
    public function saveAutoReply(Request $request, Mailbox $mailbox): RedirectResponse
    {
        $this->authorize('update', $mailbox);

        $validated = $request->validate([
            'auto_reply_enabled' => 'boolean',
            'auto_reply_subject' => 'nullable|required_if:auto_reply_enabled,true|string|max:128',
            'auto_reply_message' => 'nullable|required_if:auto_reply_enabled,true|string',
            'auto_bcc' => 'nullable|email|max:255',
        ]);

        $validated['auto_reply_enabled'] = $request->has('auto_reply_enabled');

        $mailbox->update($validated);

        return redirect()->route('mailboxes.auto_reply', $mailbox)
            ->with('success', 'Auto-reply settings saved successfully.');
    }
}
