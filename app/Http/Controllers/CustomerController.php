<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): View
    {
        $query = Customer::query();

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereHas('emails', function ($q) use ($search) {
                        // @phpstan-ignore-next-line - Closure receives Builder, not HasMany
                        $q->where('email', 'like', "%{$search}%");
                    });
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('customers.index', compact('customers'));
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'email' => 'required|email|unique:customer_emails,email',
        ]);

        $customer = Customer::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? '',
        ]);

        $customer->emails()->create([
            'email' => $validated['email'],
            'type' => 'primary',
        ]);

        return redirect()->route('customers.show', $customer);
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer): View
    {
        $customer->load('conversations.mailbox', 'conversations.folder');

        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:60',
            'timezone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:12',
            'country' => 'nullable|string|max:2',
            'notes' => 'nullable|string',
            'emails' => 'nullable|array',
            'emails.*.email' => 'required_with:emails|email',
            'emails.*.type' => 'required_with:emails|string',
            'social_profiles' => 'nullable|array',
            'websites' => 'nullable|array',
        ]);

        $customer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully.',
            'customer' => $customer,
        ]);
    }

    /**
     * Merge customers.
     */
    public function merge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_id' => 'required|exists:customers,id',
            'target_id' => 'required|exists:customers,id|different:source_id',
        ]);

        DB::beginTransaction();

        try {
            /** @var \App\Models\Customer $source */
            $source = Customer::findOrFail($validated['source_id']);
            /** @var \App\Models\Customer $target */
            $target = Customer::findOrFail($validated['target_id']);

            // Move conversations
            Conversation::where('customer_id', $source->id)
                ->update(['customer_id' => $target->id]);

            // Merge emails (avoiding duplicates)
            $targetEmailAddresses = $target->emails->pluck('email')->toArray();

            foreach ($source->emails as $email) {
                if (! in_array($email->email, $targetEmailAddresses)) {
                    $email->update(['customer_id' => $target->id]);
                }
            }

            // Delete source customer
            $source->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customers merged successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to merge customers: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search customers via AJAX.
     */
    public function ajax(Request $request): JsonResponse
    {
        $action = $request->input('action');

        switch ($action) {
            case 'search':
                $query = $request->input('q');

                $customers = Customer::query()
                    ->where(function ($q) use ($query) {
                        $q->where('first_name', 'like', "%{$query}%")
                            ->orWhere('last_name', 'like', "%{$query}%")
                            ->orWhereHas('emails', function ($q) use ($query) {
                                // @phpstan-ignore-next-line - Closure receives Builder, not HasMany
                                $q->where('email', 'like', "%{$query}%");
                            });
                    })
                    ->with('emails')
                    ->limit(25)
                    ->get();

                return response()->json([
                    'results' => $customers->map(function ($customer) {
                        return [
                            'id' => $customer->id,
                            'text' => $customer->getFullName() . ' (' . $customer->getMainEmail() . ')',
                        ];
                    }),
                ]);

            case 'conversations':
                $customerId = $request->input('customer_id');

                $conversations = Conversation::query()
                    ->where('customer_id', $customerId)
                    ->where('state', 2)
                    ->with(['mailbox', 'folder', 'user'])
                    ->orderBy('last_reply_at', 'desc')
                    ->limit(50)
                    ->get();

                return response()->json([
                    'success' => true,
                    'conversations' => $conversations,
                ]);

            default:
                return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }
}
