<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Nwidart\Modules\Facades\Module;

class RbacSeeder extends Seeder
{
    public function run()
    {
        // 1. Core Permissions (Always Available)
        $corePermissions = [
            'view_tickets' => 'View Tickets',
            'manage_tickets' => 'Manage Tickets',
            'approve_users' => 'Approve Users',
            'view_reports' => 'View Reports',
            'access_admin_panel' => 'Access Admin Panel',
        ];

        foreach ($corePermissions as $name => $label) {
            Permission::firstOrCreate(['name' => $name], ['label' => $label]);
        }

        // 2. Dynamic Module Permissions & High Level Access
        // We now iterate through all enabled modules and look for their 'permissions'
        // config in module.json.
        if (class_exists(Module::class)) {
            foreach (Module::allEnabled() as $module) {
                // Ensure alias is lowercase for consistency
                $alias = strtolower((string) $module->getAlias());
                
                // A. Create High-Level Access Permission (The "All or Nothing" Switch)
                Permission::firstOrCreate(
                    ['name' => "access_{$alias}"], 
                    ['label' => "Access " . $module->getName() . " Module"]
                );

                // B. Read Granular Permissions from module.json
                // Expected format in module.json:
                // "permissions": {
                //    "view_assets": "View Assets",
                //    "manage_assets": "Manage Assets"
                // }
                $definedPermissions = $module->get('permissions', []);
                
                if (is_array($definedPermissions)) {
                    foreach ($definedPermissions as $permName => $permLabel) {
                        Permission::firstOrCreate(['name' => $permName], ['label' => $permLabel]);
                    }
                }
            }
        }

        // 3. Create Roles
        $mspAdmin = Role::firstOrCreate(['name' => 'MSP Admin'], ['label' => 'MSP Administrator']);
        $mspFinance = Role::firstOrCreate(['name' => 'MSP Finance'], ['label' => 'MSP Finance']);
        $mspTech = Role::firstOrCreate(['name' => 'MSP Technician'], ['label' => 'MSP Technician']);
        
        $clientAdmin = Role::firstOrCreate(['name' => 'Client Admin'], ['label' => 'Client Administrator']);
        $clientUser = Role::firstOrCreate(['name' => 'Client User'], ['label' => 'Client User']);

        // 4. Assign Permissions to Roles
        
        // MSP Admin gets EVERYTHING
        $mspAdmin->permissions()->sync(Permission::all());

        // MSP Tech gets Ticket, Asset, CRM access
        $techPermissions = Permission::whereIn('name', [
            'view_tickets', 'manage_tickets', 'access_admin_panel',
            'access_crm', 'view_crm', 'manage_crm',
            'access_assetmanagement', 'view_assets', 'manage_assets',
            'access_emailmigration',
            'access_pib', // Techs might need to see billing?
        ])->pluck('id');
        $mspTech->permissions()->sync($techPermissions);

        // MSP Finance gets Billing, Payment, CRM
        $financePermissions = Permission::whereIn('name', [
            'access_crm', 'view_crm',
            'access_pib', 'view_billing', 'manage_billing',
            'access_payment', 'manage_payments'
        ])->pluck('id');
        $mspFinance->permissions()->sync($financePermissions);

        // Client Admin gets access to their own data
        $clientAdminPermissions = Permission::whereIn('name', [
            'view_tickets', 'approve_users',
            'access_assetmanagement', 'view_assets',
            'access_pib', 'view_billing'
        ])->pluck('id');
        $clientAdmin->permissions()->sync($clientAdminPermissions);

        // Client User gets basic ticket access
        $clientUser->permissions()->sync(Permission::whereIn('name', ['view_tickets'])->pluck('id'));
    }
}
