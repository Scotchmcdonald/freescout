<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Contact;
use Modules\Crm\Models\ContactPermission;

class PermissionMatrixController extends Controller
{
    /**
     * Display the Contact & Permission Matrix
     */
    public function index(Request $request)
    {
        $query = Client::with(['contacts.permissions']);

        // Filter by client if specified
        if ($request->filled('client_id')) {
            $query->where('id', $request->client_id);
        }

        $clients = $query->orderBy('name')->get();
        
        // Get all permission types for the matrix header
        $permissionTypes = ContactPermission::getPermissionTypes();

        // Get filter options
        $allClients = Client::orderBy('name')->get();

        return view('admin.crm.permission-matrix', compact('clients', 'permissionTypes', 'allClients'));
    }

    /**
     * Bulk update permissions for multiple contacts
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'updates' => 'required|array',
            'updates.*.contact_id' => 'required|exists:crm_contacts,id',
            'updates.*.permission_type' => 'required|in:view_only,billing_admin,full_access,none',
        ]);

        foreach ($validated['updates'] as $update) {
            $contact = Contact::findOrFail($update['contact_id']);
            
            if ($update['permission_type'] === 'none') {
                // Remove all permissions
                $contact->permissions()->delete();
            } else {
                // Upsert permission
                ContactPermission::updateOrCreate(
                    [
                        'contact_id' => $contact->id,
                        'permission_type' => $update['permission_type'],
                    ],
                    [
                        'scope' => 'client',
                        'allowed_actions' => ContactPermission::getActionsByType($update['permission_type']),
                        'granted_by' => auth()->id(),
                    ]
                );
            }
        }

        return redirect()->back()->with('success', count($validated['updates']) . ' permissions updated successfully.');
    }

    /**
     * Apply a role template to all contacts of a specific client
     */
    public function applyTemplate(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'permission_type' => 'required|in:view_only,billing_admin,full_access',
        ]);

        $client = Client::with('contacts')->findOrFail($validated['client_id']);
        $count = 0;

        foreach ($client->contacts as $contact) {
            ContactPermission::updateOrCreate(
                [
                    'contact_id' => $contact->id,
                    'permission_type' => $validated['permission_type'],
                ],
                [
                    'scope' => 'client',
                    'allowed_actions' => ContactPermission::getActionsByType($validated['permission_type']),
                    'granted_by' => auth()->id(),
                ]
            );
            $count++;
        }

        return redirect()->back()->with('success', "Applied {$validated['permission_type']} role to {$count} contacts.");
    }
}
