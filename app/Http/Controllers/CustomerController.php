<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
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
                    ->orWhereHas('emails', function ($emailQuery) use ($searchTerm) {
                        $emailQuery->whereRaw('email like ?', ["%{$searchTerm}%"]);
                    });
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('customers.index', compact('customers'));
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $email = $validated['email'];
        if (!is_string($email)) {
            return back()->withErrors(['email' => 'Invalid email address']);
        }

        $customer = Customer::create($email, [
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
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $validated = $request->validated();

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
                                $q->whereRaw('email like ?', ["%{$searchQuery}%"]);
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
                    ->where('state', Conversation::STATE_PUBLISHED)
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

            case 'migrate_email':
                return $this->ajaxMigrateEmail($request);

            case 'add_social_profile':
                return $this->ajaxAddSocialProfile($request);

            case 'delete_social_profile':
                return $this->ajaxDeleteSocialProfile($request);

            case 'add_website':
                return $this->ajaxAddWebsite($request);

            case 'delete_website':
                return $this->ajaxDeleteWebsite($request);

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

        // Reset all emails to not main (type = 2)
        $customer->emails()->update(['type' => \App\Models\Email::TYPE_SECONDARY]);

        // Set the new main email (type = 1)
        $customer->emails()->where('id', $validated['email_id'])->update(['type' => \App\Models\Email::TYPE_PRIMARY]);

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
        $phones = (array) ($customer->phones ?? []);

        $phones[] = $validated['phone'];

        $customer->update(['phones' => array_values(array_unique($phones))]);

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

        $phones = (array) ($customer->phones ?? []);

        if (isset($phones[$validated['phone_index']])) {
            unset($phones[$validated['phone_index']]);
            $phones = array_values($phones); // Re-index
        }

        $customer->update(['phones' => $phones]);

        return response()->json([
            'success' => true,
            'message' => __('Phone deleted successfully'),
        ]);
    }

    /**
     * AJAX: Migrate email from one customer to another.
     */
    protected function ajaxMigrateEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_id' => 'required|integer|exists:emails,id',
            'source_customer_id' => 'required|integer|exists:customers,id',
            'target_customer_id' => 'required|integer|exists:customers,id|different:source_customer_id',
        ]);

        /** @var \App\Models\Customer $sourceCustomer */
        $sourceCustomer = Customer::findOrFail($validated['source_customer_id']);

        /** @var \App\Models\Customer $targetCustomer */
        $targetCustomer = Customer::findOrFail($validated['target_customer_id']);

        // Get the email
        $email = $sourceCustomer->emails()->find($validated['email_id']);
        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => __('Email not found for source customer'),
            ]);
        }
        /** @var \App\Models\Email $email */

        // Ensure source customer has at least one other email
        if ($sourceCustomer->emails()->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => __('Source customer must retain at least one email'),
            ]);
        }

        // Move the email to target customer
        $email->update(['customer_id' => $targetCustomer->id]);

        // If this was the main email, set a new main for source customer
        if ($email->type === \App\Models\Email::TYPE_PRIMARY) {
            $newMain = $sourceCustomer->emails()->first();
            if ($newMain instanceof \App\Models\Email) {
                $newMain->update(['type' => \App\Models\Email::TYPE_PRIMARY]);
            }
        }

        // Also migrate any conversations associated with this email
        $emailAddress = $email->email;
        Conversation::where('customer_id', $sourceCustomer->id)
            ->where('customer_email', $emailAddress)
            ->update(['customer_id' => $targetCustomer->id]);

        return response()->json([
            'success' => true,
            'message' => __('Email and associated conversations migrated successfully'),
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
                        $q->whereRaw('email like ?', ["%{$searchQuery}%"]);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('customers.index', compact('customers'));
    }

    /**
     * AJAX: Add social profile to customer.
     */
    protected function ajaxAddSocialProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'type' => 'required|string|in:twitter,facebook,linkedin,instagram,github',
            'value' => 'required|string|max:255',
        ]);

        /** @var \App\Models\Customer $customer */
        $customer = Customer::findOrFail($validated['customer_id']);

        // Get current social profiles
        $profiles = (array) ($customer->social_profiles ?? []);

        // Add new profile (overwrite if same type exists)
        $profiles[$validated['type']] = $validated['value'];

        $customer->update(['social_profiles' => $profiles]);

        return response()->json([
            'success' => true,
            'message' => __('Social profile added successfully'),
        ]);
    }

    /**
     * AJAX: Delete social profile from customer.
     */
    protected function ajaxDeleteSocialProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'type' => 'required|string|in:twitter,facebook,linkedin,instagram,github',
        ]);

        /** @var \App\Models\Customer $customer */
        $customer = Customer::findOrFail($validated['customer_id']);

        $profiles = (array) ($customer->social_profiles ?? []);

        unset($profiles[$validated['type']]);

        $customer->update(['social_profiles' => $profiles]);

        return response()->json([
            'success' => true,
            'message' => __('Social profile deleted successfully'),
        ]);
    }

    /**
     * AJAX: Add website to customer.
     */
    protected function ajaxAddWebsite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'url' => 'required|url|max:255',
        ]);

        /** @var \App\Models\Customer $customer */
        $customer = Customer::findOrFail($validated['customer_id']);

        // Get current websites
        $websites = (array) ($customer->websites ?? []);

        // Add new website if not already present
        if (!in_array($validated['url'], $websites)) {
            $websites[] = $validated['url'];
        }

        $customer->update(['websites' => $websites]);

        return response()->json([
            'success' => true,
            'message' => __('Website added successfully'),
        ]);
    }

    /**
     * AJAX: Delete website from customer.
     */
    protected function ajaxDeleteWebsite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'website_index' => 'required|integer|min:0',
        ]);

        /** @var \App\Models\Customer $customer */
        $customer = Customer::findOrFail($validated['customer_id']);

        $websites = (array) ($customer->websites ?? []);

        if (isset($websites[$validated['website_index']])) {
            unset($websites[$validated['website_index']]);
            $websites = array_values($websites); // Re-index
        }

        $customer->update(['websites' => $websites]);

        return response()->json([
            'success' => true,
            'message' => __('Website deleted successfully'),
        ]);
    }
}
