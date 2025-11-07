<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::orderBy('created_at', 'desc')->paginate(50);

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
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
    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load('mailboxes', 'conversations');

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
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

                return response()->json([
                    'success' => true,
                    'users' => $users->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->getFullName(),
                            'email' => $user->email,
                            'photo_url' => $user->photo_url,
                        ];
                    }),
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
}
