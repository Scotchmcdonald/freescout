<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Customer;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                    ->orWhere('phones', 'like', "%{$searchTerm}%")
                    ->orWhereHas('emails', function ($q) use ($searchTerm) {
                        /** @phpstan-ignore-next-line */
                        $q->where('email', 'like', "%{$searchTerm}%");
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
            'email' => 'required|email|unique:emails,email',
        ]);

        $customer = Customer::create($validated['email'], [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? '',
        ]);

        if (! $customer) {
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
                            ->orWhere('phones', 'like', "%{$searchQuery}%")
                            ->orWhereHas('emails', function ($q) use ($searchQuery) {
                                /** @phpstan-ignore-next-line */
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
                            'text' => $customer->getFullName().' ('.$customer->getMainEmail().')',
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

            case 'add_email':
                return $this->ajaxAddEmail($request);

            case 'delete_email':
                return $this->ajaxDeleteEmail($request);

            case 'set_main_email':
                return $this->ajaxSetMainEmail($request);

            case 'upload_photo':
                return $this->ajaxUploadPhoto($request);

            case 'delete_photo':
                return $this->ajaxDeletePhoto($request);

            case 'add_phone':
                return $this->ajaxAddPhone($request);

            case 'delete_phone':
                return $this->ajaxDeletePhone($request);

            default:
                return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }

    /**
     * AJAX: Add email to customer.
     */
    protected function ajaxAddEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'email' => 'required|email',
        ]);

        /** @var \App\Models\Customer $customer */
        $customer = Customer::findOrFail($validated['customer_id']);

        // Check if email already exists
        if (\App\Models\Email::where('email', $validated['email'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('This email is already in use by another customer'),
            ]);
        }

        $customer->emails()->create([
            'email' => $validated['email'],
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Email added successfully'),
        ]);
    }

    /**
     * AJAX: Delete email from customer.
     */
    protected function ajaxDeleteEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'email_id' => 'required|integer|exists:emails,id',
        ]);

        /** @var \App\Models\Customer $customer */
        $customer = Customer::findOrFail($validated['customer_id']);

        // Ensure customer has at least one email
        if ($customer->emails()->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => __('Customer must have at least one email'),
            ]);
        }

        $customer->emails()->where('id', $validated['email_id'])->delete();

        return response()->json([
            'success' => true,
            'message' => __('Email deleted successfully'),
        ]);
    }

    /**
     * AJAX: Set main email for customer.
     */
    protected function ajaxSetMainEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'email_id' => 'required|integer|exists:emails,id',
        ]);

        /** @var \App\Models\Customer $customer */
        $customer = Customer::findOrFail($validated['customer_id']);

        // Reset all emails to not main
        $customer->emails()->update(['is_main' => false]);

        // Set the new main email
        $customer->emails()->where('id', $validated['email_id'])->update(['is_main' => true]);

        return response()->json([
            'success' => true,
            'message' => __('Main email updated successfully'),
        ]);
    }

    /**
     * AJAX: Upload customer photo.
     */
    protected function ajaxUploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        /** @var \App\Models\Customer $customer */
        $customer = Customer::findOrFail($request->input('customer_id'));

        // Delete old photo
        if ($customer->photo_url && ! str_starts_with($customer->photo_url, 'http')) {
            $fullPath = storage_path('app/public/'.$customer->photo_url);
            if (file_exists($fullPath)) {
                try {
                    unlink($fullPath);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to delete old customer photo: '.$e->getMessage());
                }
            }
        }

        // Store new photo
        $path = $request->file('photo')->store('customer_photos', 'public');

        $customer->update(['photo_url' => $path]);

        return response()->json([
            'success' => true,
            'photo_url' => asset('storage/'.$path),
            'message' => __('Photo uploaded successfully'),
        ]);
    }

    /**
     * AJAX: Delete customer photo.
     */
    protected function ajaxDeletePhoto(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
        ]);

        /** @var \App\Models\Customer $customer */
        $customer = Customer::findOrFail($validated['customer_id']);

        // Delete the file if it's a local path
        if ($customer->photo_url && ! str_starts_with($customer->photo_url, 'http')) {
            $fullPath = storage_path('app/public/'.$customer->photo_url);
            if (file_exists($fullPath)) {
                try {
                    unlink($fullPath);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed to delete customer photo: '.$e->getMessage());
                }
            }
        }

        $customer->update(['photo_url' => null]);

        return response()->json([
            'success' => true,
            'message' => __('Photo deleted successfully'),
        ]);
    }

    /**
     * AJAX: Add phone to customer.
     */
    protected function ajaxAddPhone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'phone' => 'required|string|max:60',
        ]);

        /** @var \App\Models\Customer $customer */
        $customer = Customer::findOrFail($validated['customer_id']);

        // Get current phones and add new one
        $phones = $customer->phones ? json_decode($customer->phones, true) : [];
        if (! is_array($phones)) {
            $phones = [];
        }

        $phones[] = $validated['phone'];

        $customer->update(['phones' => json_encode(array_unique($phones))]);

        return response()->json([
            'success' => true,
            'message' => __('Phone added successfully'),
        ]);
    }

    /**
     * AJAX: Delete phone from customer.
     */
    protected function ajaxDeletePhone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'phone_index' => 'required|integer|min:0',
        ]);

        /** @var \App\Models\Customer $customer */
        $customer = Customer::findOrFail($validated['customer_id']);

        $phones = $customer->phones ? json_decode($customer->phones, true) : [];
        if (! is_array($phones)) {
            $phones = [];
        }

        if (isset($phones[$validated['phone_index']])) {
            unset($phones[$validated['phone_index']]);
            $phones = array_values($phones); // Re-index
        }

        $customer->update(['phones' => json_encode($phones)]);

        return response()->json([
            'success' => true,
            'message' => __('Phone deleted successfully'),
        ]);
    }

    /**
     * Show customer conversations page.
     */
    public function conversations(Customer $customer): View|ViewFactory
    {
        $conversations = $customer->conversations()
            ->with(['mailbox', 'folder', 'user'])
            ->orderBy('last_reply_at', 'desc')
            ->paginate(25);

        return view('customers.conversations', compact('customer', 'conversations'));
    }

    /**
     * Show merge customer form.
     */
    public function mergeForm(Customer $customer): View|ViewFactory
    {
        return view('customers.merge', compact('customer'));
    }

    /**
     * Delete the specified customer.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user || $user->role !== \App\Models\User::ROLE_ADMIN) {
            abort(403);
        }

        if ($customer->conversations()->exists()) {
            return back()->withErrors([
                'error' => 'Cannot delete customer with existing conversations.',
            ]);
        }

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    /**
     * Search customers.
     */
    public function search(Request $request): View|ViewFactory
    {
        $query = $request->input('q', '');
        $searchQuery = is_string($query) ? $query : '';

        $customers = Customer::query()
            ->where(function ($q) use ($searchQuery) {
                $q->where('first_name', 'like', "%{$searchQuery}%")
                    ->orWhere('last_name', 'like', "%{$searchQuery}%")
                    ->orWhere('phones', 'like', "%{$searchQuery}%")
                    ->orWhereHas('emails', function ($q) use ($searchQuery) {
                        /** @phpstan-ignore-next-line */
                        $q->where('email', 'like', "%{$searchQuery}%");
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('customers.index', compact('customers'));
    }
}
