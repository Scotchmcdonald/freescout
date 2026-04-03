<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Contact;
use Modules\Crm\Models\ContactPermission;

class PermissionMatrixController extends Controller
{
    /**
     * Ordered list of permission types from least to most privileged.
     * Used to enforce "you cannot grant more than you have".
     */
    private const PRIVILEGE_ORDER = ['view_only', 'billing_admin', 'full_access'];

    /**
     * Display the Contact & Permission Matrix
     */
    public function index(Request $request): View
    {
        $query = Client::with(['contacts.permissions']);

        // Filter by client if specified
        if ($request->filled('client_id')) {
            $query->where('id', $request->client_id);
        }

        $clients = $query->orderBy('name')->get();

        // Get all permission types for the matrix header
        $permissionTypes = ContactPermission::getPermissionTypes();
        $permissionDescriptions = ContactPermission::getPermissionDescriptions();
        $actionDescriptions = ContactPermission::getActionDescriptions();

        // Get filter options (withCount for quick-template contact preview)
        $allClients = Client::withCount('contacts')->orderBy('name')->get();

        return view('admin.crm.permission-matrix', compact(
            'clients',
            'permissionTypes',
            'permissionDescriptions',
            'actionDescriptions',
            'allClients',
        ));
    }

    /**
     * Bulk update permissions for multiple contacts.
     *
     * Security constraints:
     * - Each contact must belong to a client the requesting admin has access to.
     * - An admin cannot elevate a contact to a privilege tier higher than their own.
     * - All changes are activity-logged.
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'updates'                      => 'required|array',
            'updates.*.contact_id'         => 'required|exists:crm_contacts,id',
            'updates.*.permission_type'    => 'required|in:view_only,billing_admin,full_access,none',
        ]);

        /** @var \App\Models\User $admin */
        $admin = auth()->user();

        $count = 0;

        foreach ($validated['updates'] as $update) {
            /** @var Contact $contact */
            $contact = Contact::with('client')->findOrFail($update['contact_id']);

            // Tenant isolation: contact must belong to a client this admin can manage.
            if ($contact->client && ! $admin->hasCompanyAccess($contact->client->company_id ?? 0)) {
                abort(403, 'You do not have permission to modify contacts for this client.');
            }

            $newType = $update['permission_type'];

            // Privilege escalation guard: cannot grant a level higher than your own.
            if ($newType !== 'none' && ! $this->adminCanGrant($admin, $newType)) {
                abort(403, "You cannot assign the '{$newType}' role as it exceeds your own access level.");
            }

            $oldPermission = $contact->permissions()->first();
            $oldType = $oldPermission?->permission_type ?? 'none';

            if ($newType === 'none') {
                $contact->permissions()->delete();
            } else {
                ContactPermission::updateOrCreate(
                    [
                        'contact_id'      => $contact->id,
                        'permission_type' => $newType,
                    ],
                    [
                        'scope'           => 'client',
                        'allowed_actions' => ContactPermission::getActionsByType($newType),
                        'granted_by'      => $admin->id,
                    ]
                );
            }

            // Activity log each change individually.
            activity()
                ->causedBy($admin)
                ->performedOn($contact)
                ->withProperties(['old' => $oldType, 'new' => $newType])
                ->log('contact_permission_updated');

            $count++;
        }

        return redirect()->back()->with('success', $count.' permission(s) updated successfully.');
    }

    /**
     * Apply a role template to all contacts of a specific client.
     *
     * Security constraints identical to bulkUpdate.
     */
    public function applyTemplate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id'       => 'required|exists:clients,id',
            'permission_type' => 'required|in:view_only,billing_admin,full_access',
        ]);

        /** @var \App\Models\User $admin */
        $admin = auth()->user();

        /** @var Client $client */
        $client = Client::with('contacts')->findOrFail($validated['client_id']);

        // Tenant isolation.
        if (! $admin->hasCompanyAccess($client->company_id ?? 0)) {
            abort(403, 'You do not have permission to manage contacts for this client.');
        }

        // Privilege escalation guard.
        if (! $this->adminCanGrant($admin, $validated['permission_type'])) {
            abort(403, "You cannot assign the '{$validated['permission_type']}' role as it exceeds your own access level.");
        }

        $count = 0;

        foreach ($client->contacts as $contact) {
            /** @var Contact $contact */
            $oldPermission = $contact->permissions()->first();
            $oldType = $oldPermission?->permission_type ?? 'none';

            ContactPermission::updateOrCreate(
                [
                    'contact_id'      => $contact->id,
                    'permission_type' => $validated['permission_type'],
                ],
                [
                    'scope'           => 'client',
                    'allowed_actions' => ContactPermission::getActionsByType($validated['permission_type']),
                    'granted_by'      => $admin->id,
                ]
            );

            activity()
                ->causedBy($admin)
                ->performedOn($contact)
                ->withProperties(['old' => $oldType, 'new' => $validated['permission_type']])
                ->log('contact_permission_bulk_updated');

            $count++;
        }

        return redirect()->back()->with('success', "Applied '{$validated['permission_type']}' role to {$count} contact(s) for {$client->name}.");
    }

    /**
     * Determine if the requesting admin can grant the given permission type.
     * An admin cannot promote someone to a level above their own highest permission.
     * Super-admins (role === admin) bypass this check.
     */
    private function adminCanGrant(\App\Models\User $admin, string $permissionType): bool
    {
        // System admins have no restriction.
        if ($admin->role === \App\Models\User::ROLE_ADMIN) {
            return true;
        }

        $targetIndex = array_search($permissionType, self::PRIVILEGE_ORDER, true);

        if ($targetIndex === false) {
            return false;
        }

        // Find the highest privilege the admin holds across any of their clients.
        $adminHighestIndex = 0;
        $adminPermissions = ContactPermission::whereHas('contact', function ($q) use ($admin) {
            $q->where('email', $admin->email);
        })->pluck('permission_type');

        foreach ($adminPermissions as $held) {
            $heldIndex = array_search($held, self::PRIVILEGE_ORDER, true);
            if ($heldIndex !== false && $heldIndex > $adminHighestIndex) {
                $adminHighestIndex = $heldIndex;
            }
        }

        return $targetIndex <= $adminHighestIndex;
    }
}
