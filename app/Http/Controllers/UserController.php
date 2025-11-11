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

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

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
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Extract mailboxes before updating user
        $mailboxes = $validated['mailboxes'] ?? null;
        unset($validated['mailboxes']);

        $user->update($validated);

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
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->conversations()->exists()) {
            return back()->withErrors([
                'error' => 'Cannot delete user with existing conversations. Reassign them first.',
            ]);
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
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

            default:
                return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
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
            $user->photo_url = $path;
        }

        // Update user
        $user->fill([
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
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
}
