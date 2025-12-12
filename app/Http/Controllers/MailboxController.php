<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMailboxRequest;
use App\Http\Requests\UpdateMailboxRequest;
use App\Models\Conversation;
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
            ->where('state', Conversation::STATE_PUBLISHED)
            ->when($request->input('folder'), function ($query, $folderId) {
                $query->where('folder_id', $folderId);
            })
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
     * Show the form for creating a new mailbox.
     */
    public function create(Request $request): View|ViewFactory
    {
        $this->authorize('create', Mailbox::class);

        // Get all non-admin users to assign to mailbox
        $users = User::where('role', '!=', User::ROLE_ADMIN)
            ->where('status', '!=', User::STATUS_DELETED)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('mailboxes.create', compact('users'));
    }

    /**
     * Store a newly created mailbox.
     */
    public function store(StoreMailboxRequest $request): RedirectResponse
    {
        $this->authorize('create', Mailbox::class);

        $validated = $request->validated();

        // Map string values to integers
        $mappings = [
            'out_method' => ['mail' => 1, 'sendmail' => 2, 'smtp' => 3],
            'out_encryption' => ['none' => 0, 'ssl' => 1, 'tls' => 2],
            'in_protocol' => ['imap' => 1, 'pop3' => 2],
            'in_encryption' => ['none' => 0, 'ssl' => 1, 'tls' => 2],
        ];

        foreach ($mappings as $field => $map) {
            if (isset($validated[$field]) && is_string($validated[$field])) {
                $validated[$field] = $map[$validated[$field]] ?? $validated[$field];
            }
        }

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

        // Remove users from validated data before creating mailbox
        $users = $validated['users'] ?? [];
        unset($validated['users']);

        // Sanitize name
        if (isset($validated['name']) && is_string($validated['name'])) {
            $validated['name'] = strip_tags($validated['name']);
        }

        $mailbox = Mailbox::create($validated);

        // Sync users to mailbox
        if (! empty($users) && is_array($users)) {
            $mailbox->users()->sync($users);
        }

        return redirect()->route('mailboxes.index')
            ->with('success', 'Mailbox created successfully.');
    }

    /**
     * Update the specified mailbox.
     */
    public function update(UpdateMailboxRequest $request, Mailbox $mailbox): RedirectResponse
    {
        $this->authorize('update', $mailbox);

        $validated = $request->validated();

        // Map string values to integers
        $mappings = [
            'out_method' => ['mail' => 1, 'sendmail' => 2, 'smtp' => 3],
            'out_encryption' => ['none' => 0, 'ssl' => 1, 'tls' => 2],
            'in_protocol' => ['imap' => 1, 'pop3' => 2],
            'in_encryption' => ['none' => 0, 'ssl' => 1, 'tls' => 2],
        ];

        foreach ($mappings as $field => $map) {
            if (isset($validated[$field]) && is_string($validated[$field])) {
                $validated[$field] = $map[$validated[$field]] ?? $validated[$field];
            }
        }

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

        // Sanitize name if present
        if (isset($validated['name']) && is_string($validated['name'])) {
            $validated['name'] = strip_tags($validated['name']);
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
            'in_imap_folders' => 'nullable|string|max:255',
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
        $permissions = $validated['permissions'] ?? [];
        foreach ($permissions as $userId => $access) {
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

    /**
     * Handle AJAX requests for mailbox operations.
     */
    public function ajax(Request $request, ImapService $imapService): JsonResponse
    {
        $response = [
            'status' => 'error',
            'msg' => '',
        ];

        /** @var \App\Models\User $user */
        $user = $request->user();

        switch ($request->action) {
            case 'fetch_test':
                $mailboxId = $request->mailbox_id;
                $mailbox = Mailbox::find(is_numeric($mailboxId) ? (int) $mailboxId : 0);

                if (! $mailbox) {
                    $response['msg'] = 'Mailbox not found';
                } elseif (! $user->can('admin', $mailbox)) {
                    $response['msg'] = 'Not enough permissions';
                }

                if (! $response['msg']) {
                    /** @var Mailbox $mailbox */
                    try {
                        $testResult = $imapService->testConnection($mailbox);

                        if ($testResult['success']) {
                            $response['status'] = 'success';
                            $response['msg_success'] = $testResult['message'];
                        } else {
                            $response['msg'] = $testResult['message'];
                        }
                    } catch (\Exception $e) {
                        $response['msg'] = 'Error occurred connecting to the server: '.$e->getMessage();
                    }
                }
                break;

            case 'imap_folders':
                $mailboxId = $request->mailbox_id;
                $mailbox = Mailbox::find(is_numeric($mailboxId) ? (int) $mailboxId : 0);

                if (! $mailbox) {
                    $response['msg'] = 'Mailbox not found';
                } elseif (! $user->can('admin', $mailbox)) {
                    $response['msg'] = 'Not enough permissions';
                }

                $response['folders'] = [];

                if (! $response['msg']) {
                    /** @var Mailbox $mailbox */
                    try {
                        $folderResult = $imapService->getFolders($mailbox);

                        if ($folderResult['success']) {
                            $response['folders'] = $folderResult['folders'];
                            $response['status'] = 'success';

                            if (count($response['folders']) > 0) {
                                $response['msg_success'] = 'IMAP folders retrieved: '.implode(', ', $response['folders']);
                            } else {
                                $response['msg_success'] = 'Connected, but no IMAP folders found';
                            }
                        } else {
                            $response['msg'] = $folderResult['message'];
                        }
                    } catch (\Exception $e) {
                        $response['msg'] = $e->getMessage();
                    }
                }
                break;

            default:
                $response['msg'] = 'Unknown action';
                break;
        }

        return response()->json($response);
    }

    /**
     * Show mailbox advanced settings (aliases, from name, ticket options, signature).
     */
    public function advancedSettings(Request $request, Mailbox $mailbox): View|ViewFactory
    {
        $this->authorize('update', $mailbox);

        // Get from name options
        $fromNameOptions = [
            1 => __('Mailbox Name'),
            2 => __('User Name'),
            3 => __('User Name + Mailbox Name'),
            4 => __('Custom'),
        ];

        // Get ticket assignment options
        $ticketAssigneeOptions = [
            1 => __('Leave unassigned'),
            2 => __('To replying user'),
        ];

        return view('mailboxes.advanced_settings', compact('mailbox', 'fromNameOptions', 'ticketAssigneeOptions'));
    }

    /**
     * Save mailbox advanced settings.
     */
    public function saveAdvancedSettings(Request $request, Mailbox $mailbox): RedirectResponse
    {
        $this->authorize('update', $mailbox);

        $validated = $request->validate([
            'aliases' => 'nullable|string|max:1000',
            'aliases_reply' => 'nullable|boolean',
            'from_name' => 'required|integer|in:1,2,3,4',
            'from_name_custom' => 'nullable|string|max:255|required_if:from_name,4',
            'ticket_status' => 'nullable|integer|in:1,2',
            'ticket_assignee' => 'nullable|integer|in:1,2',
            'before_reply' => 'nullable|string|max:5000',
            'signature' => 'nullable|string|max:10000',
            'ratings' => 'nullable|boolean',
        ]);

        // Process booleans
        $validated['aliases_reply'] = $request->has('aliases_reply');
        $validated['ratings'] = $request->has('ratings');

        // Process aliases (convert newlines to commas)
        if (!empty($validated['aliases'])) {
            $aliasLines = preg_split('/[\r\n,]+/', $validated['aliases']);
            if ($aliasLines === false) {
                $aliasLines = [];
            }
            $cleanAliases = [];
            foreach ($aliasLines as $alias) {
                $alias = trim($alias);
                if (!empty($alias) && filter_var($alias, FILTER_VALIDATE_EMAIL)) {
                    $cleanAliases[] = $alias;
                }
            }
            $validated['aliases'] = implode(',', $cleanAliases);
        }

        $mailbox->update($validated);

        return redirect()->route('mailboxes.advanced_settings', $mailbox)
            ->with('success', 'Advanced settings saved successfully.');
    }

    /**
     * Connect to OAuth provider.
     */
    public function oauthConnect(Request $request, string $provider): \Illuminate\Http\RedirectResponse
    {
        $mailboxId = $request->input('mailbox_id');
        if (!$mailboxId) {
            return redirect()->back()->with('error', 'Mailbox ID is missing');
        }
        
        $mailbox = Mailbox::findOrFail($mailboxId);
        if (!($mailbox instanceof Mailbox)) {
            return redirect()->back()->with('error', 'Mailbox not found');
        }
        $this->authorize('update', $mailbox);
        
        session(['oauth_mailbox_id' => $mailbox->id]);
        
        $type = $request->input('type', 'incoming');
        session(['oauth_type' => $type]);
        
        if ($type == 'incoming') {
            $clientId = $mailbox->in_username;
        } else {
            $clientId = $mailbox->out_username;
        }
        
        if (!$clientId) {
             return redirect()->back()->with('error', 'Client ID is missing in settings');
        }
        
        $params = [
            'client_id' => $clientId,
            'state' => $mailbox->id,
        ];
        
        $url = \App\Misc\OAuth::getAuthorizationUrl($provider, $params);
        
        return redirect()->away($url);
    }

    /**
     * OAuth Callback.
     */
    public function oauthCallback(Request $request): \Illuminate\Http\RedirectResponse
    {
        $code = $request->input('code');
        $state = $request->input('state');
        $error = $request->input('error');
        
        if ($error) {
            $errorMessage = is_string($error) ? $error : 'Unknown error';
            return redirect()->route('mailboxes.index')->with('error', 'OAuth Error: ' . $errorMessage);
        }
        
        if (!$state) {
             $state = session('oauth_mailbox_id');
        }
        
        if (!$state) {
            return redirect()->route('mailboxes.index')->with('error', 'Unknown mailbox');
        }
        
        $mailbox = Mailbox::findOrFail($state);
        if (!($mailbox instanceof Mailbox)) {
            return redirect()->route('mailboxes.index')->with('error', 'Mailbox not found');
        }
        $this->authorize('update', $mailbox);
        
        $sessionType = session('oauth_type', 'incoming');
        $type = is_string($sessionType) || is_int($sessionType) || is_float($sessionType) ? (string) $sessionType : 'incoming';
        
        if ($type == 'incoming') {
            $clientId = $mailbox->in_username;
            $encryptedPassword = $mailbox->in_password;
            try {
                $clientSecret = is_string($encryptedPassword) ? decrypt($encryptedPassword) : '';
            } catch (\Exception $e) {
                $clientSecret = $encryptedPassword;
            }
        } else {
            $clientId = $mailbox->out_username;
            $encryptedPassword = $mailbox->out_password;
            try {
                $clientSecret = is_string($encryptedPassword) ? decrypt($encryptedPassword) : '';
            } catch (\Exception $e) {
                $clientSecret = $mailbox->out_password;
            }
        }
        
        $params = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
        ];
        
        $provider = \App\Misc\OAuth::PROVIDER_MICROSOFT;
        
        $tokenData = \App\Misc\OAuth::getAccessToken($provider, $params);
        
        if (!empty($tokenData['error'])) {
            $errorMsg = is_string($tokenData['error']) || is_int($tokenData['error']) || is_float($tokenData['error']) ? (string) $tokenData['error'] : 'Unknown error';
            return redirect()->route('mailboxes.connection.'.$type, $mailbox)->with('error', 'Failed to get access token: ' . $errorMsg);
        }
        
        $meta = (array) ($mailbox->meta ?? []);
        $meta['oauth'] = $tokenData;
        $mailbox->meta = $meta;
        $mailbox->save();
        
        return redirect()->route('mailboxes.connection.'.$type, $mailbox)->with('success', 'Connected successfully');
    }
    
    /**
     * Disconnect OAuth.
     */
    public function oauthDisconnect(Request $request, Mailbox $mailbox): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $mailbox);
        
        // Meta is stored as array or null
        $meta = is_array($mailbox->meta) ? $mailbox->meta : [];
        
        if (isset($meta['oauth'])) {
            unset($meta['oauth']);
        }
        $mailbox->meta = $meta;
        $mailbox->save();
        
        return redirect()->back()->with('success', 'Disconnected successfully');
    }
    
    /**
     * Send a test email to verify SMTP settings.
     */
    public function sendTestEmail(Request $request, Mailbox $mailbox, \App\Services\SmtpService $smtpService): JsonResponse
    {
        $this->authorize('update', $mailbox);

        $request->validate([
            'test_email' => 'required|email',
        ]);

        $testEmailInput = $request->input('test_email');
        $testEmail = is_string($testEmailInput) || is_int($testEmailInput) || is_float($testEmailInput) ? (string) $testEmailInput : '';

        try {
            $result = $smtpService->testConnection($mailbox, $testEmail);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
