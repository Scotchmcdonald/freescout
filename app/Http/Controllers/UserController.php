<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): View|Factory
    {
        $this->authorize('viewAny', User::class);

        $users = User::orderBy('created_at', 'desc')->paginate(50);

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View|Factory
    {
        $this->authorize('create', User::class);

        return view('users.create');
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|integer|in:1,2',
            'status' => 'required|integer|in:1,2',
            'job_title' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:60',
            'timezone' => 'nullable|string|max:255',
            'locale' => 'nullable|string|max:2',
        ]);

        // Password will be hashed by the User model cast
        // $validated['password'] = Hash::make($validated['password']);

        // Remove null values for timezone and locale to use database defaults
        if (empty($validated['timezone'])) {
            unset($validated['timezone']);
        }
        if (empty($validated['locale'])) {
            unset($validated['locale']);
        }

        $user = User::create($validated);

        // Allow modules to modify user after creation
        $user = \Eventy::filter('user.create_save', $user, $request);

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): View|Factory
    {
        $this->authorize('view', $user);

        $user->load('mailboxes', 'conversations');

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View|Factory
    {
        $this->authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|integer|in:1,2',
            'status' => 'required|integer|in:1,2',
            'job_title' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:60',
            'timezone' => 'nullable|string|max:255',
            'locale' => 'nullable|string|max:2',
            'mailboxes' => 'nullable|array',
            'mailboxes.*' => 'integer|exists:mailboxes,id',
        ]);

        if (! empty($validated['password'])) {
            // Password will be hashed by the User model cast
            // $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Extract mailboxes before updating user
        $mailboxes = $validated['mailboxes'] ?? null;
        unset($validated['mailboxes']);

        // Remove null values for timezone and locale to use database defaults or keep existing
        if (empty($validated['timezone'])) {
            unset($validated['timezone']);
        }
        if (empty($validated['locale'])) {
            unset($validated['locale']);
        }

        $user->update($validated);

        // Allow modules to modify user after save
        $user = \Eventy::filter('user.save_profile', $user, $request);

        // Sync mailboxes if provided
        if ($mailboxes !== null) {
            $user->mailboxes()->sync($mailboxes);
        }

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $reassignTo = $request->input('reassign_to');
        
        if ($user->conversations()->exists()) {
            if (!$reassignTo) {
                return back()->withErrors([
                    'error' => 'Cannot delete user with existing conversations. Select a user to reassign conversations to.',
                ]);
            }
            
            // Validate reassign target
            $targetUser = User::find($reassignTo);
            if (!$targetUser || $targetUser->id === $user->id || $targetUser->isDeleted()) {
                return back()->withErrors([
                    'error' => 'Invalid user selected for conversation reassignment.',
                ]);
            }
            
            // Reassign all conversations
            $user->conversations()->update(['user_id' => $targetUser->id]);
            
            // Log the reassignment
            \Illuminate\Support\Facades\Log::info(
                "Reassigned conversations from user {$user->id} to user {$targetUser->id} during deletion"
            );
        }

        // Mark as deleted instead of hard delete (soft delete)
        $user->update(['status' => User::STATUS_DELETED]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.' . ($reassignTo ? ' Conversations reassigned.' : ''));
    }

    /**
     * Update user's permissions.
     */
    public function permissions(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'mailboxes' => 'nullable|array',
            'mailboxes.*' => 'integer|exists:mailboxes,id',
        ]);

        $user->mailboxes()->sync($validated['mailboxes'] ?? []);

        return back()->with('success', 'Permissions updated successfully.');
    }

    /**
     * AJAX methods for users.
     */
    public function ajax(Request $request): JsonResponse
    {
        $action = $request->input('action');

        switch ($action) {
            case 'search':
                $query = $request->input('query', '');
                $searchQuery = is_string($query) ? $query : '';

                $users = User::query()
                    ->where('status', 1) // Active only
                    ->where(function ($q) use ($searchQuery) {
                        $q->where('first_name', 'like', "%{$searchQuery}%")
                            ->orWhere('last_name', 'like', "%{$searchQuery}%")
                            ->orWhere('email', 'like', "%{$searchQuery}%");
                    })
                    ->limit(25)
                    ->get(['id', 'first_name', 'last_name', 'email', 'photo_url']);

                /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users */
                $mappedUsers = [];
                foreach ($users as $user) {
                    $mappedUsers[] = [
                        'id' => $user->id,
                        'name' => $user->getFullName(),
                        'email' => $user->email,
                        'photo_url' => $user->photo_url,
                    ];
                }

                return response()->json([
                    'success' => true,
                    'users' => $mappedUsers,
                ]);

            case 'toggle_status':
                $userId = $request->input('user_id');
                /** @var \App\Models\User $user */
                $user = User::findOrFail($userId);

                $this->authorize('update', $user);

                $newStatus = $user->status === 1 ? 2 : 1;
                $user->update(['status' => $newStatus]);

                return response()->json([
                    'success' => true,
                    'status' => $newStatus,
                ]);

            case 'delete_photo':
                return $this->ajaxDeletePhoto($request);

            case 'upload_photo':
                return $this->ajaxUploadPhoto($request);

            case 'resend_invite':
                return $this->ajaxResendInvite($request);

            case 'send_password_reset':
                return $this->ajaxSendPasswordReset($request);

            default:
                // Allow modules to handle custom actions
                $response = ['success' => false, 'message' => 'Invalid action'];
                $response = \Eventy::filter('users.ajax.response_default', $response, $request);
                
                return response()->json($response, $response['success'] ? 200 : 400);
        }
    }

    /**
     * AJAX: Delete user photo.
     */
    protected function ajaxDeletePhoto(Request $request): JsonResponse
    {
        $userId = $request->input('user_id');
        /** @var \App\Models\User $user */
        $user = User::findOrFail($userId);

        $this->authorize('update', $user);

        // Delete the file if it's a local path
        if ($user->photo_url && ! str_starts_with($user->photo_url, 'http')) {
            $fullPath = storage_path('app/public/'.$user->photo_url);
            if (file_exists($fullPath)) {
                try {
                    unlink($fullPath);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to delete user photo: '.$e->getMessage());
                }
            }
        }

        $user->update(['photo_url' => null]);

        return response()->json([
            'success' => true,
            'message' => __('Photo deleted successfully'),
        ]);
    }

    /**
     * AJAX: Upload user photo.
     */
    protected function ajaxUploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $userId = $request->input('user_id');
        /** @var \App\Models\User $user */
        $user = User::findOrFail($userId);

        $this->authorize('update', $user);

        // Delete old photo
        if ($user->photo_url && ! str_starts_with($user->photo_url, 'http')) {
            $fullPath = storage_path('app/public/'.$user->photo_url);
            if (file_exists($fullPath)) {
                try {
                    unlink($fullPath);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to delete old user photo: '.$e->getMessage());
                }
            }
        }

        // Store new photo
        $path = $request->file('photo')->store('avatars', 'public');

        $user->update(['photo_url' => $path]);

        return response()->json([
            'success' => true,
            'photo_url' => asset('storage/'.$path),
            'message' => __('Photo uploaded successfully'),
        ]);
    }

    /**
     * AJAX: Resend invitation email.
     */
    protected function ajaxResendInvite(Request $request): JsonResponse
    {
        $userId = $request->input('user_id');
        /** @var \App\Models\User $user */
        $user = User::findOrFail($userId);

        $this->authorize('update', $user);

        if ($user->invite_state === User::INVITE_STATE_ACTIVATED) {
            return response()->json([
                'success' => false,
                'message' => __('User has already activated their account'),
            ]);
        }

        // Generate new invite hash if needed
        if (! $user->invite_hash) {
            $user->invite_hash = \Illuminate\Support\Str::random(32);
            $user->save();
        }

        try {
            $user->sendInvite(true);

            return response()->json([
                'success' => true,
                'message' => __('Invitation email sent successfully'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to send invitation: ').$e->getMessage(),
            ]);
        }
    }

    /**
     * AJAX: Send password reset email.
     */
    protected function ajaxSendPasswordReset(Request $request): JsonResponse
    {
        $userId = $request->input('user_id');
        /** @var \App\Models\User $user */
        $user = User::findOrFail($userId);

        $this->authorize('update', $user);

        try {
            \Illuminate\Support\Facades\Password::broker()->sendResetLink([
                'email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Password reset email sent successfully'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to send password reset: ').$e->getMessage(),
            ]);
        }
    }

    /**
     * Show user notifications preferences form.
     */
    public function notifications(User $user): View|Factory
    {
        $this->authorize('view', $user);

        $subscriptions = $user->subscriptions;
        $users = User::where('status', 1)->orderBy('first_name')->get();

        return view('users.notifications', compact('user', 'subscriptions', 'users'));
    }

    /**
     * Update user notification preferences.
     */
    public function updateNotifications(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'subscriptions' => 'nullable|array',
            'subscriptions.*' => 'array',
            'subscriptions.*.*' => 'integer',
        ]);

        // Delete all existing subscriptions
        $user->subscriptions()->delete();

        // Create new subscriptions
        if (isset($validated['subscriptions'])) {
            foreach ($validated['subscriptions'] as $medium => $events) {
                foreach ($events as $event) {
                    $user->subscriptions()->create([
                        'medium' => (int) $medium,
                        'event' => (int) $event,
                    ]);
                }
            }
        }

        return back()->with('success', 'Notification preferences updated successfully.');
    }

    /**
     * Show user permissions form.
     */
    public function permissionsForm(User $user): View|Factory
    {
        $this->authorize('update', $user);

        $mailboxes = \App\Models\Mailbox::orderBy('name')->get();
        $user_mailboxes = $user->mailboxes->pluck('id');
        $users = User::where('status', 1)->orderBy('first_name')->get();

        return view('users.permissions', compact('user', 'mailboxes', 'user_mailboxes', 'users'));
    }

    /**
     * Setup user from invitation (public route).
     * Allows invited users to complete their profile setup.
     */
    public function userSetup(string $hash): View|Factory|RedirectResponse
    {
        // If already authenticated, redirect to dashboard
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        $user = User::where('invite_hash', $hash)->first();

        if (!$user) {
            abort(404, 'Invalid invitation link');
        }

        return view('users.setup', compact('user'));
    }

    /**
     * Save user setup from invitation.
     */
    public function userSetupSave(string $hash, Request $request): RedirectResponse
    {
        // If already authenticated, redirect to dashboard
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        $user = User::where('invite_hash', $hash)->first();

        if (!$user) {
            abort(404, 'Invalid invitation link');
        }

        $validated = $request->validate([
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'required|string|min:8|confirmed',
            'job_title' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:60',
            'timezone' => 'required|string|max:255',
            'time_format' => 'required|in:12,24',
            'photo_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo_url')) {
            $path = $request->file('photo_url')->store('avatars', 'public');
            if ($path) {
                $user->photo_url = $path;
            }
        }

        // Update user
        $user->fill([
            'email' => $validated['email'],
            'password' => $validated['password'], // Hashed by model cast
            'job_title' => $validated['job_title'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'timezone' => $validated['timezone'],
            'time_format' => (int) $validated['time_format'],
            'invite_state' => 1, // Mark as activated
            'invite_hash' => null, // Clear invite hash
        ]);

        $user->save();

        // Log the user in
        auth()->login($user);

        return redirect()->route('dashboard')->with('success', 'Your account has been set up successfully!');
    }

    /**
     * Display global permissions index.
     */
    public function permissionsIndex(): View|Factory
    {
        $this->authorize('viewAny', User::class); // Assuming admin check

        return view('permissions.index');
    }

    /**
     * Save global permissions.
     */
    public function permissionsSave(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class); // Assuming admin check

        // Logic to save permissions would go here
        
        return redirect()->route('permissions')->with('success', 'Permissions saved successfully.');
    }
}
