<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RbacSeeder extends Seeder
{
    public function run()
    {
        // Create Permissions
        $permissions = [
            'view_billing' => 'View Billing & Finance',
            'manage_assets' => 'Manage Assets',
            'approve_users' => 'Approve Users',
            'view_tickets' => 'View Tickets',
            'manage_tickets' => 'Manage Tickets',
            // Module Permissions
            'view_inventory' => 'View Inventory',
            'manage_inventory' => 'Manage Inventory',
            'view_crm' => 'View CRM',
            'manage_crm' => 'Manage CRM',
            'view_email_migration' => 'View Email Migration',
            'manage_email_migration' => 'Manage Email Migration',
            'view_dev_feedback' => 'View Dev Feedback',
            'manage_dev_feedback' => 'Manage Dev Feedback',
        ];

        foreach ($permissions as $name => $label) {
            Permission::firstOrCreate(['name' => $name], ['label' => $label]);
        }

        // Create Roles
        $mspAdmin = Role::firstOrCreate(['name' => 'MSP Admin'], ['label' => 'MSP Administrator']);
        $mspFinance = Role::firstOrCreate(['name' => 'MSP Finance'], ['label' => 'MSP Finance']);
        $consultant = Role::firstOrCreate(['name' => 'Consultant'], ['label' => 'External Consultant']);
        $clientAdmin = Role::firstOrCreate(['name' => 'Client Admin'], ['label' => 'Client Administrator']);
        $clientUser = Role::firstOrCreate(['name' => 'Client User'], ['label' => 'Client User']);

        // Assign Permissions to Roles
        
        // MSP Admin gets everything
        $mspAdmin->permissions()->sync(Permission::all());

        // MSP Finance gets billing and CRM view
        $mspFinance->permissions()->sync(Permission::whereIn('name', ['view_billing', 'view_crm'])->pluck('id'));

        // Consultant gets tickets, assets, inventory, CRM view
        $consultant->permissions()->sync(Permission::whereIn('name', [
            'view_tickets', 'manage_tickets', 'manage_assets', 
            'view_inventory', 'manage_inventory', 'view_crm'
        ])->pluck('id'));

        // Client Admin gets tickets, assets, approve users, view inventory
        $clientAdmin->permissions()->sync(Permission::whereIn('name', [
            'view_tickets', 'manage_tickets', 'manage_assets', 'approve_users',
            'view_inventory'
        ])->pluck('id'));

        // Client User gets view tickets
        $clientUser->permissions()->sync(Permission::whereIn('name', ['view_tickets'])->pluck('id'));
    }
}
