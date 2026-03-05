<?php

declare(strict_types=1);

/**
 * RBAC Configuration — Single Source of Truth
 *
 * This file defines:
 *   - Core permissions (not owned by any module)
 *   - Default roles and their properties
 *   - Default role→permission assignments (baseline)
 *
 * Module-specific permissions are declared in each module's `module.json` under
 * the "permissions" key and are auto-registered by the RbacSeeder.
 *
 * Running `php artisan db:seed --class=RbacSeeder` is always safe (idempotent).
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Core Permissions
    |--------------------------------------------------------------------------
    |
    | Permissions that are not owned by any specific module. These are always
    | available regardless of which modules are enabled.
    |
    | Format: 'permission_name' => [
    |     'label'      => 'Human-readable label',
    |     'group'      => 'UI group heading',
    |     'sort_order' => int (lower = higher in the list),
    | ]
    |
    */

    'core_permissions' => [
        'view_tickets' => [
            'label' => 'View Tickets',
            'group' => 'Tickets',
            'sort_order' => 10,
        ],
        'manage_tickets' => [
            'label' => 'Manage Tickets',
            'group' => 'Tickets',
            'sort_order' => 20,
        ],
        'approve_users' => [
            'label' => 'Approve Users',
            'group' => 'Users',
            'sort_order' => 30,
        ],
        'manage_users' => [
            'label' => 'Manage Users',
            'group' => 'Users',
            'sort_order' => 40,
        ],
        'view_reports' => [
            'label' => 'View Reports',
            'group' => 'Reporting',
            'sort_order' => 50,
        ],
        'access_admin_panel' => [
            'label' => 'Access Admin Panel',
            'group' => 'Settings',
            'sort_order' => 60,
        ],
        'view_settings' => [
            'label' => 'View Settings',
            'group' => 'Settings',
            'sort_order' => 65,
        ],
        'manage_settings' => [
            'label' => 'Manage Settings',
            'group' => 'Settings',
            'sort_order' => 70,
        ],
        'manage_rbac' => [
            'label' => 'Manage Roles & Permissions',
            'group' => 'Settings',
            'sort_order' => 80,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Roles
    |--------------------------------------------------------------------------
    |
    | Roles created by the seeder. The key is the role name (must be unique).
    |
    | Properties:
    |   - label:          Display name in the UI
    |   - is_super_admin: If true, this role bypasses ALL permission checks
    |   - scope:          'internal' (MSP staff) or 'client' (external users)
    |   - sort_order:     Display order in the matrix UI
    |
    */

    'roles' => [
        'MSP Admin' => [
            'label' => 'MSP Administrator',
            'is_super_admin' => true,
            'scope' => 'internal',
            'sort_order' => 10,
        ],
        'MSP Finance' => [
            'label' => 'MSP Finance',
            'is_super_admin' => false,
            'scope' => 'internal',
            'sort_order' => 20,
        ],
        'MSP Technician' => [
            'label' => 'MSP Technician',
            'is_super_admin' => false,
            'scope' => 'internal',
            'sort_order' => 30,
        ],
        'MSP Reporter' => [
            'label' => 'MSP Reporter',
            'is_super_admin' => false,
            'scope' => 'internal',
            'sort_order' => 40,
        ],
        'Client Admin' => [
            'label' => 'Client Administrator',
            'is_super_admin' => false,
            'scope' => 'client',
            'sort_order' => 50,
        ],
        'Client Finance' => [
            'label' => 'Client Finance',
            'is_super_admin' => false,
            'scope' => 'client',
            'sort_order' => 60,
        ],
        'Client User' => [
            'label' => 'Client User',
            'is_super_admin' => false,
            'scope' => 'client',
            'sort_order' => 70,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Role → Permission Assignments
    |--------------------------------------------------------------------------
    |
    | Baseline permissions for each role. The seeder uses syncWithoutDetaching()
    | so permissions added via the UI are never removed, but these baseline
    | permissions are always guaranteed to exist.
    |
    | The special value '*' means "all permissions" (used for super admin).
    |
    */

    'role_permissions' => [

        'MSP Admin' => '*', // Wildcard — gets every permission

        'MSP Technician' => [
            // Core
            'view_tickets', 'manage_tickets', 'access_admin_panel',
            // CRM
            'view_crm', 'manage_crm',
            // Asset Management
            'view_assets', 'manage_assets',
            // Email Migration
            'view_email_migration', 'manage_email_migration',
            'view_email_migration_settings',
            // Case Manager
            'view_case_manager', 'manage_case_manager',
            'view_case_manager_settings',
            // TSDM
            'view_tsdm', 'manage_tsdm', 'issue_tsdm_activations',
            'view_tsdm_settings',
            // Software Subscriptions
            'view_software_subscriptions',
            // Knowledge Base
            'view_knowledge_base', 'manage_knowledge_base',
            // Contract Manager
            'view_contracts', 'manage_contracts',
            // Google Admin
            'view_google_admin', 'manage_google_admin',
            'view_google_admin_settings',
            // Action1
            'view_action1',
            'view_action1_settings',
            // Alerts
            'view_alerts', 'manage_alerts',
        ],

        'MSP Finance' => [
            // Core
            'view_tickets', 'view_reports',
            // CRM (read-only)
            'view_crm',
            // PIB / Billing
            'view_billing', 'manage_billing',
            // Payment
            'manage_payments',
            // Contract Manager
            'view_contracts', 'manage_contracts',
            // Software Subscriptions
            'view_software_subscriptions', 'manage_software_subscriptions',
        ],

        'MSP Reporter' => [
            // Core (read-only)
            'view_tickets', 'view_reports',
            // CRM (read-only)
            'view_crm',
            // Asset Management (read-only)
            'view_assets',
            // PIB (read-only)
            'view_billing',
        ],

        'Client Admin' => [
            // Core
            'view_tickets', 'approve_users',
            // Asset Management (read-only)
            'view_assets',
            // PIB / Billing (read-only)
            'view_billing',
            // Knowledge Base (read-only)
            'view_knowledge_base',
        ],

        'Client Finance' => [
            // Core
            'view_tickets',
            // PIB / Billing (read-only)
            'view_billing',
        ],

        'Client User' => [
            'view_tickets',
            'view_knowledge_base',
        ],
    ],
];
