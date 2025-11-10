<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory as ViewFactory;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): View|ViewFactory
    {
        $query = Customer::query();
        $search = $request->input('search', '');
        $searchTerm = is_string($search) ? $search : '';

        // Search filter
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('emails', function ($q) use ($searchTerm) {
                        // @phpstan-ignore-next-line
                        $q->where('email', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('customers.index', compact('customers'));
    }

    /**
     * Store a newly created customer in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'email' => 'required|email|unique:customer_emails,email',
        ]);

        $customer = Customer::create($validated['email'], [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? '',
        ]);
        
        if (!$customer) {
            return back()->withErrors(['email' => 'Invalid email address']);
        }

        return redirect()->route('customers.show', $customer);
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer): View|ViewFactory
    {
        $customer->load('conversations.mailbox', 'conversations.folder');

        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer): View|ViewFactory
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
                $query = $request->input('q', '');
                $searchQuery = is_string($query) ? $query : '';

                /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Customer> $customers */
                $customers = Customer::query()
                    ->where(function ($q) use ($searchQuery) {
                        $q->where('first_name', 'like', "%{$searchQuery}%")
                            ->orWhere('last_name', 'like', "%{$searchQuery}%")
                            ->orWhereHas('emails', function ($q) use ($searchQuery) {
                                // @phpstan-ignore-next-line
                                $q->where('email', 'like', "%{$searchQuery}%");
                            });
                    })
                    ->with('emails')
                    ->limit(25)
                    ->get();

                return response()->json([
                    'results' => $customers->map(function (\App\Models\Customer $customer) {
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
