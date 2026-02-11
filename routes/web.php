<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CollisionController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MailboxController;
use App\Http\Controllers\ModulesController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TagController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WebhookGatewayController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\MilestoneController;
use App\Http\Controllers\AlertSubscriptionController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Api\ConversationController as ApiConversationController;
use App\Http\Controllers\PublicAttachmentController;
use App\Http\Controllers\TrackingController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Public Attachment Download
Route::get('/attachments/{id}/public-download', [PublicAttachmentController::class, 'download'])->name('attachments.public_download');

// Global Search
Route::get('/global-search', [App\Http\Controllers\GlobalSearchController::class, 'index'])->name('search.global')->middleware(['auth']);

// Tracking Pixel
Route::get('/track/pixel/{id}', [TrackingController::class, 'pixel'])->name('track.pixel');

// User invitation setup (public route for invited users)
Route::get('/user/setup/{hash}', [UserController::class, 'userSetup'])->name('user_setup');
Route::post('/user/setup/{hash}', [UserController::class, 'userSetupSave'])->name('user_setup.save');

// Web Cron (public route with hash verification)
Route::get('/cron/{hash}', [SystemController::class, 'cron'])->name('system.cron');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Impersonation routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/impersonate/{user}', [ImpersonationController::class, 'impersonate'])->name('impersonate');
    Route::post('/impersonate/leave', [ImpersonationController::class, 'leave'])->name('impersonate.leave');
});

if (app()->environment('local', 'testing')) {
    // Chaos Testing
    Route::get('/chaos/network-timeout', [\App\Http\Controllers\ChaosController::class, 'networkTimeout'])->name('chaos.network_timeout');
    Route::get('/chaos/disk-full', [\App\Http\Controllers\ChaosController::class, 'diskFull'])->name('chaos.disk_full');
}

Route::middleware(['auth', 'verified'])->group(function () {
    // RBAC
    Route::get('/rbac/matrix', [\App\Http\Controllers\RbacController::class, 'index'])->name('rbac.matrix');
    Route::post('/rbac/update', [\App\Http\Controllers\RbacController::class, 'update'])->name('rbac.update');
    Route::post('/rbac/roles', [\App\Http\Controllers\RbacController::class, 'storeRole'])->name('rbac.roles.store');
    Route::delete('/rbac/roles/{role}', [\App\Http\Controllers\RbacController::class, 'destroyRole'])->name('rbac.roles.destroy');

        // Collision Detection
    Route::post('/conversations/{id}/viewing', [CollisionController::class, 'viewing'])->name('conversations.viewing');
    
    // Mailboxes
    Route::get('/mailboxes', [MailboxController::class, 'index'])->name('mailboxes.index');
    Route::get('/mailboxes/create', [MailboxController::class, 'create'])->name('mailboxes.create');
    Route::post('/mailboxes', [MailboxController::class, 'store'])->name('mailboxes.store');
    Route::get('/mailbox/{mailbox}', [MailboxController::class, 'show'])->name('mailboxes.view');
    Route::get('/mailbox/{mailbox}/show', [MailboxController::class, 'show'])->name('mailboxes.show'); // Alias for tests
    Route::match(['patch', 'put'], '/mailbox/{mailbox}', [MailboxController::class, 'update'])->name('mailboxes.update');
    Route::delete('/mailbox/{mailbox}', [MailboxController::class, 'destroy'])->name('mailboxes.destroy');
    Route::get('/mailbox/{mailbox}/settings', [MailboxController::class, 'settings'])->name('mailboxes.settings');
    Route::get('/mailbox/{mailbox}/advanced-settings', [MailboxController::class, 'advancedSettings'])->name('mailboxes.advanced_settings');
    Route::post('/mailbox/{mailbox}/advanced-settings', [MailboxController::class, 'saveAdvancedSettings'])->name('mailboxes.save_advanced_settings');
    Route::get('/mailbox/{mailbox}/connection/incoming', [MailboxController::class, 'connectionIncoming'])->name('mailboxes.connection.incoming');
    Route::get('/mailbox/{mailbox}/connection-incoming', [MailboxController::class, 'connectionIncoming'])->name('mailboxes.connection-incoming'); // Alias for tests
    Route::post('/mailbox/{mailbox}/connection/incoming', [MailboxController::class, 'saveConnectionIncoming'])->name('mailboxes.save-connection-incoming');
    Route::get('/mailbox/{mailbox}/connection/outgoing', [MailboxController::class, 'connectionOutgoing'])->name('mailboxes.connection.outgoing');
    Route::get('/mailbox/{mailbox}/connection-outgoing', [MailboxController::class, 'connectionOutgoing'])->name('mailboxes.connection-outgoing'); // Alias for tests
    Route::post('/mailbox/{mailbox}/connection/outgoing', [MailboxController::class, 'saveConnectionOutgoing']);
    Route::post('/mailbox/{mailbox}/fetch-emails', [MailboxController::class, 'fetchEmails'])->name('mailboxes.fetch-emails');
    Route::post('/mailbox/ajax', [MailboxController::class, 'ajax'])->name('mailboxes.ajax');

    // OAuth
    Route::get('/mailbox/oauth/connect/{provider}', [MailboxController::class, 'oauthConnect'])->name('mailboxes.oauth_connect');
    Route::get('/mailbox/oauth/callback', [MailboxController::class, 'oauthCallback'])->name('mailboxes.oauth_callback');
    Route::get('/mailbox/oauth/disconnect/{mailbox}', [MailboxController::class, 'oauthDisconnect'])->name('mailboxes.oauth_disconnect');

    // Conversations
    Route::get('/mailbox/{mailbox}/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/mailbox/{mailbox}/conversations/list', [ConversationController::class, 'index'])->name('mailbox.conversations'); // Alias for tests
    Route::get('/conversation/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    
    // Modified for tests: use mailbox_id parameter and allow POST
    Route::get('/mailbox/{mailbox_id}/conversation/create', [ConversationController::class, 'create'])->name('conversations.create');
    Route::post('/mailbox/{mailbox_id}/conversation/create', [ConversationController::class, 'store']);
    
    Route::post('/mailbox/{mailbox}/conversation', [ConversationController::class, 'store'])->name('conversations.store');
    Route::patch('/conversation/{conversation}', [ConversationController::class, 'update'])->name('conversations.update');
    Route::post('/conversation/{conversation}/assign', [ConversationController::class, 'update'])->name('conversations.assign'); // Alias for tests
    Route::post('/conversation/{conversation}/reply', [ConversationController::class, 'reply'])->name('conversations.reply');
    Route::post('/conversation/{conversation}/update-status', [ConversationController::class, 'update'])->name('conversations.update_status'); // Alias for tests
    Route::post('/conversations/ajax', [ConversationController::class, 'ajax'])->name('conversations.ajax');
    Route::delete('/conversation/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');
    Route::get('/conversations/search', [ConversationController::class, 'search'])->name('conversations.search');
    Route::get('/search', [ConversationController::class, 'search'])->name('search'); // Alias for tests
    Route::get('/mailbox/{mailbox}/clone-ticket/{thread}', [ConversationController::class, 'clone'])->name('conversations.clone');
    Route::post('/conversation/{conversation}/thread/{thread}/forward', [ConversationController::class, 'forward'])->name('conversations.forward');
    Route::post('/conversation/{conversation}/thread/{thread}/undo-send', [ConversationController::class, 'undoSend'])->name('conversations.undo_send');
    
    // Conversation AJAX operations
    Route::get('/conversations/ajax-html', [ConversationController::class, 'ajaxHtml'])->name('conversations.ajax_html');
    Route::post('/conversation/{conversation}/change-customer', [ConversationController::class, 'changeCustomer'])->name('conversations.change_customer');
    
    // Helpdesk/Ticket aliases for Dusk tests (point to conversation routes with default mailbox)
    Route::get('/helpdesk/tickets', function() {
        $mailbox = \App\Models\Mailbox::first();
        if (!$mailbox) {
            $mailbox = \App\Models\Mailbox::create([
                'name' => 'Support', 'email' => 'support@example.com', 'is_default' => true,
                'status' => 1, 'from_name' => 1, 'ticket_status' => 1, 'ticket_assignee' => 1, 'template' => 1, 'out_method' => 1,
            ]);
        }
        return app(\App\Http\Controllers\ConversationController::class)->index(request(), $mailbox);
    })->name('helpdesk.tickets.index');

    Route::get('/helpdesk/tickets/create', function() {
        $mailbox = \App\Models\Mailbox::first();
        if (!$mailbox) {
            // Create a default mailbox for testing if none exists
            $mailbox = \App\Models\Mailbox::create([
                'name' => 'Support',
                'email' => 'support@example.com',
                'is_default' => true,
                'status' => 1,
                'from_name' => 1,
                'ticket_status' => 1,
                'ticket_assignee' => 1,
                'template' => 1,
                'out_method' => 1,
            ]);
        }
        return app(\App\Http\Controllers\ConversationController::class)->create(request(), $mailbox);
    })->name('helpdesk.tickets.create');
    
    Route::post('/helpdesk/tickets', function() {
        $mailbox = \App\Models\Mailbox::first();
        if (!$mailbox) {
            // Create a default mailbox for testing if none exists
            $mailbox = \App\Models\Mailbox::create([
                'name' => 'Support',
                'email' => 'support@example.com',
                'is_default' => true,
                'status' => 1,
                'from_name' => 1,
                'ticket_status' => 1,
                'ticket_assignee' => 1,
                'template' => 1,
                'out_method' => 1,
            ]);
        }
        return app(\App\Http\Controllers\ConversationController::class)->store(app(\App\Http\Requests\StoreConversationRequest::class), $mailbox);
    })->name('helpdesk.tickets.store');
    Route::post('/conversation/{conversation}/merge', [ConversationController::class, 'merge'])->name('conversations.merge');
    Route::post('/conversation/{conversation}/move', [ConversationController::class, 'move'])->name('conversations.move');
    Route::post('/conversations/batch-update', [ConversationController::class, 'batchUpdate'])->name('conversations.batch_update');
    Route::put('/conversation/{conversation}/thread/{thread}', [ConversationController::class, 'updateThread'])->name('conversations.update_thread');
    Route::put('/conversation/{conversation}/settings', [ConversationController::class, 'updateSettings'])->name('conversations.update_settings');
    
    // Chats view
    Route::get('/conversations/chats', [ConversationController::class, 'chats'])->name('conversations.chats');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/conversations', [CustomerController::class, 'conversations'])->name('customers.conversations');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::get('/customers/{customer}/merge', [CustomerController::class, 'mergeForm'])->name('customers.merge.form');
    Route::patch('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::post('/customers/merge', [CustomerController::class, 'merge'])->name('customers.merge');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    // Users (admin only)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/user/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/user/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::get('/user/{user}/password', [UserController::class, 'passwordForm'])->name('users.password');
    Route::post('/user/{user}/password', [UserController::class, 'updatePassword'])->name('users.password.update');
    Route::get('/user/{user}/notifications', [UserController::class, 'notifications'])->name('users.notifications');
    Route::post('/user/{user}/notifications', [UserController::class, 'updateNotifications'])->name('users.notifications.update');
    Route::get('/user/{user}/permissions', [UserController::class, 'permissionsForm'])->name('users.permissions');
    Route::post('/user/{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions.update');
    Route::match(['patch', 'put'], '/user/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/user/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/ajax', [UserController::class, 'ajax'])->name('users.ajax');

    // Settings (admin only)
    Route::middleware(['admin'])->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::get('/settings/index', [SettingsController::class, 'index'])->name('settings.index'); // Alias for tests
        Route::get('/settings/general', [SettingsController::class, 'general'])->name('settings.general'); // New route for tests
        Route::get('/settings/security', [SettingsController::class, 'security'])->name('settings.security'); // New route for tests
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('/settings/email', [SettingsController::class, 'email'])->name('settings.email');
        Route::post('/settings/email', [SettingsController::class, 'updateEmail'])->name('settings.email.update');
        Route::get('/settings/data-import', [SettingsController::class, 'dataImport'])->name('settings.data_import');
        Route::get('/settings/alerts', [SettingsController::class, 'alerts'])->name('settings.alerts');
        Route::put('/settings/alerts', [SettingsController::class, 'updateAlerts'])->name('settings.alerts.update');
        Route::get('/settings/system', [SettingsController::class, 'system'])->name('settings.system');
        Route::post('/settings/cache/clear', [SettingsController::class, 'clearCache'])->name('settings.cache.clear');
        Route::post('/settings/cache/clear-alias', [SettingsController::class, 'clearCache'])->name('system.clear-cache'); // Alias for tests
        Route::post('/settings/migrate', [SettingsController::class, 'migrate'])->name('settings.migrate');
        Route::post('/settings/test-smtp', [SettingsController::class, 'testSmtp'])->name('settings.test-smtp');
        Route::post('/settings/test-imap', [SettingsController::class, 'testImap'])->name('settings.test-imap');
        Route::post('/settings/validate-smtp', [SettingsController::class, 'validateSmtp'])->name('settings.validate-smtp');
        
        // Migrations
        Route::get('/settings/migrations', [SettingsController::class, 'migrations'])->name('settings.migrations');
    });

    // System (admin only)
    Route::middleware(['admin'])->group(function () {
        Route::get('/system', [SystemController::class, 'index'])->name('system');
        Route::get('/system/update', [SystemController::class, 'update'])->name('system.update');
        Route::post('/system/update', [SystemController::class, 'performUpdate'])->name('system.perform_update');
        Route::post('/system/update/pull', [SystemController::class, 'pullUpdate'])->name('system.pull_update');
        Route::get('/system/update/check-banner', [SystemController::class, 'checkUpdateBanner'])->name('system.check_update_banner');
        Route::post('/system/ajax', [SystemController::class, 'ajax'])->name('system.ajax');
        Route::get('/system/diagnostics', [SystemController::class, 'diagnostics'])->name('system.diagnostics');
        Route::get('/system/logs', [SystemController::class, 'logs'])->name('system.logs');
        Route::get('/system/logs/download', [SystemController::class, 'downloadLogs'])->name('system.logs.download');
        
        // Failed Jobs
        Route::get('/system/failed-jobs', [SystemController::class, 'failedJobs'])->name('system.failed_jobs');
        Route::post('/system/failed-jobs/{uuid}/retry', [SystemController::class, 'retryFailedJob'])->name('system.failed_jobs.retry');
        Route::delete('/system/failed-jobs/{uuid}', [SystemController::class, 'deleteFailedJob'])->name('system.failed_jobs.delete');
        Route::post('/system/failed-jobs/queue/delete', [SystemController::class, 'deleteFailedJobsForQueue'])->name('system.failed_jobs.delete_queue');
        Route::post('/system/failed-jobs/queue/retry', [SystemController::class, 'retryFailedJobsForQueue'])->name('system.failed_jobs.retry_queue');
        
        // System Tools
        Route::get('/system/tools', [SystemController::class, 'tools'])->name('system.tools');
        Route::post('/system/tools', [SystemController::class, 'toolsExecute'])->name('system.tools.execute');
        
        // Logs clearing
        Route::post('/system/logs/clear', [SystemController::class, 'clearLogs'])->name('system.logs.clear');
        
        // Empty folder
        Route::post('/folder/{folder}/empty', [ConversationController::class, 'emptyFolder'])->name('folders.empty');
        
        // Added for tests
        Route::get('/logs', [SystemController::class, 'logs'])->name('logs');
        Route::get('/logs/download', [SystemController::class, 'downloadLogs'])->name('logs.download');
        
        Route::get('/permissions', [UserController::class, 'permissionsIndex'])->name('permissions');
        Route::post('/permissions', [UserController::class, 'permissionsSave'])->name('permissions.save');
        
        Route::get('/webhooks', [WebhookController::class, 'index'])->name('webhooks');
        Route::get('/webhooks/create', [WebhookController::class, 'create'])->name('webhooks.create');
        Route::post('/webhooks', [WebhookController::class, 'store'])->name('webhooks.store');
        
        Route::post('/conversations/export', [ConversationController::class, 'export'])->name('conversations.export');
        Route::get('/conversations/import', [ConversationController::class, 'import'])->name('conversations.import');
    });

    // Admin - Infrastructure Resilience (Phase 6)
    Route::middleware(['admin'])->group(function () {
        // Combined Dashboard
        Route::get('/resilience', [App\Http\Controllers\Admin\ResilienceController::class, 'index'])->name('admin.resilience.index');

        Route::post('/resilience/circuit-breakers/{service}/reset', [App\Http\Controllers\Admin\ResilienceController::class, 'resetCircuit'])->name('admin.resilience.reset-circuit');

    // Event Audit Log Routes
    Route::get('/resilience/events', [App\Http\Controllers\Admin\ResilienceController::class, 'eventsAudit'])->name('admin.resilience.events-audit');
    Route::get('/resilience/events/export', [App\Http\Controllers\Admin\ResilienceController::class, 'exportEvents'])->name('admin.resilience.events-audit.export');

    // Sync Operation Monitor (Phase 8)
    Route::get('/sync-monitor', [App\Http\Controllers\Admin\SyncMonitorController::class, 'index'])->name('admin.sync-monitor.index');
    Route::get('/sync-monitor/{operation}', [App\Http\Controllers\Admin\SyncMonitorController::class, 'show'])->name('admin.sync-monitor.show');
    Route::post('/sync-monitor/{operation}/resume', [App\Http\Controllers\Admin\SyncMonitorController::class, 'resume'])->name('admin.sync-monitor.resume');
    Route::post('/sync-monitor/{operation}/retry', [App\Http\Controllers\Admin\SyncMonitorController::class, 'retry'])->name('admin.sync-monitor.retry');
    Route::post('/sync-monitor/{operation}/cancel', [App\Http\Controllers\Admin\SyncMonitorController::class, 'cancel'])->name('admin.sync-monitor.cancel');

    // Asset Management Routes (AssetManagement Module)
    Route::get('/assets/inventory', [Modules\AssetManagement\Http\Controllers\AssetController::class, 'index'])->name('admin.assets.inventory');
    Route::post('/assets/inventory', [Modules\AssetManagement\Http\Controllers\AssetController::class, 'store'])->name('admin.assets.store');
    Route::get('/assets/inventory/export', [Modules\AssetManagement\Http\Controllers\AssetController::class, 'export'])->name('admin.assets.inventory.export');
    Route::get('/assets/conflicts', [Modules\AssetManagement\Http\Controllers\AssetController::class, 'conflicts'])->name('admin.assets.conflicts');
    Route::post('/assets/conflicts/{id}/approve', [Modules\AssetManagement\Http\Controllers\AssetController::class, 'approveConflict'])->name('admin.assets.conflicts.approve');
    Route::post('/assets/conflicts/{id}/reject', [Modules\AssetManagement\Http\Controllers\AssetController::class, 'rejectConflict'])->name('admin.assets.conflicts.reject');
    Route::get('/assets/assign', [Modules\AssetManagement\Http\Controllers\AssetController::class, 'assign'])->name('admin.assets.assign');
    Route::post('/assets/assign', [Modules\AssetManagement\Http\Controllers\AssetController::class, 'storeAssignment'])->name('admin.assets.store_assignment');
    Route::get('/assets/{id}', [Modules\AssetManagement\Http\Controllers\AssetController::class, 'show'])->name('admin.assets.show');
    Route::get('/assets/{id}/edit', [Modules\AssetManagement\Http\Controllers\AssetController::class, 'edit'])->name('admin.assets.edit');
    Route::put('/assets/{id}', [Modules\AssetManagement\Http\Controllers\AssetController::class, 'update'])->name('admin.assets.update');
    Route::patch('/assets/{id}/status', [Modules\AssetManagement\Http\Controllers\AssetController::class, 'updateStatus'])->name('admin.assets.update_status');

    // Billing Operations (PIB Module)
    Route::get('/billing/variance-explorer', [Modules\PIB\Http\Controllers\BillingController::class, 'varianceExplorer'])->name('admin.billing.variance');
    Route::get('/billing/templates/create', [Modules\PIB\Http\Controllers\BillingController::class, 'createTemplate'])->name('admin.billing.templates.create');
    Route::post('/billing/templates', [Modules\PIB\Http\Controllers\BillingController::class, 'storeTemplate'])->name('admin.billing.templates.store');
    Route::get('/billing/payments/create', [Modules\PIB\Http\Controllers\BillingController::class, 'createPayment'])->name('admin.billing.payments.create');
    Route::post('/billing/payments', [Modules\PIB\Http\Controllers\BillingController::class, 'storePayment'])->name('admin.billing.payments.store');

    // User Lifecycle Dashboard
    Route::get('/users/lifecycle', [App\Http\Controllers\Admin\UserLifecycleController::class, 'index'])->name('admin.users.lifecycle');
    Route::post('/users/lifecycle/sync', [App\Http\Controllers\Admin\UserLifecycleController::class, 'sync'])->name('admin.users.lifecycle.sync');

    // CRM Permission Matrix
    Route::get('/crm/permission-matrix', [App\Http\Controllers\Admin\PermissionMatrixController::class, 'index'])->name('admin.crm.permission-matrix');
    Route::post('/crm/permission-matrix/bulk-update', [App\Http\Controllers\Admin\PermissionMatrixController::class, 'bulkUpdate'])->name('admin.crm.permission-matrix.bulk-update');
    Route::post('/crm/permission-matrix/apply-template', [App\Http\Controllers\Admin\PermissionMatrixController::class, 'applyTemplate'])->name('admin.crm.permission-matrix.apply-template');
    
    // CRM Custom Fields
    Route::resource('crm/fields', \Modules\Crm\Http\Controllers\CustomFieldController::class)->names('crm.fields');
    Route::post('crm/fields/save-values/{entity_type}/{id}', [\Modules\Crm\Http\Controllers\CustomFieldController::class, 'saveValues'])->name('crm.fields.save_values');

    // CRM Clients (Create/Store)
    Route::get('/crm/clients/create', [\Modules\Crm\Http\Controllers\ClientController::class, 'create'])->name('admin.crm.clients.create');
    Route::post('/crm/clients', [\Modules\Crm\Http\Controllers\ClientController::class, 'store'])->name('admin.crm.clients.store');
    
    // CRM Contacts
    Route::post('/crm/contacts', [\Modules\Crm\Http\Controllers\ContactController::class, 'store'])->name('crm.contacts.store');
    Route::delete('/crm/contacts/{id}', [\Modules\Crm\Http\Controllers\ContactController::class, 'destroy'])->name('crm.contacts.destroy');
});

// Client edit routes (alias - resolves CRM Client to Customer for billing tests)
Route::middleware(['admin'])->group(function () {
    Route::get('/clients/{id}/edit', function ($id) {
        // Try to find existing Customer, or create one from CRM Client
        $customer = \App\Models\Customer::find($id);
        if (!$customer) {
            $client = \Modules\Crm\Models\Client::findOrFail($id);
            $email = $client->email;
            if (!$email) {
                /** @var \Modules\Crm\Models\ClientUser|null $clientUser */
                $clientUser = $client->users()->first();
                $email = $clientUser ? $clientUser->email : 'client-' . $client->id . '@portal.local';
            }
            $customer = \App\Models\Customer::firstOrCreate(
                ['email' => $email],
                ['first_name' => $client->name, 'company' => $client->name]
            );
        }
        return view('customers.edit', compact('customer'));
    })->name('clients.edit');

    Route::patch('/clients/{id}', function (\Illuminate\Http\Request $request, $id) {
        $customer = \App\Models\Customer::find($id);
        if (!$customer) {
            $client = \Modules\Crm\Models\Client::findOrFail($id);
            $email = $client->email;
            if (!$email) {
                /** @var \Modules\Crm\Models\ClientUser|null $clientUser */
                $clientUser = $client->users()->first();
                $email = $clientUser ? $clientUser->email : 'client-' . $client->id . '@portal.local';
            }
            $customer = \App\Models\Customer::firstOrCreate(
                ['email' => $email],
                ['first_name' => $client->name, 'company' => $client->name]
            );
        }
        $customer->update($request->only(['first_name', 'last_name', 'company', 'default_hourly_rate', 'notes']));
        return redirect()->back()->with('success', 'Client updated');
    })->name('clients.update');
});

    // Modules (admin only)
    Route::middleware(['admin'])->group(function () {
        Route::get('/modules/list', [ModulesController::class, 'index'])->name('modules');
        Route::get('/modules/activity', [ModulesController::class, 'activityLog'])->name('modules.activity');
        Route::get('/modules/install', [ModulesController::class, 'showInstallForm'])->name('modules.install.form');
        Route::post('/modules/install', [ModulesController::class, 'install'])->name('modules.install');
        Route::post('/modules/install-initiate', [ModulesController::class, 'initiateInstall'])->name('modules.install.initiate');
        Route::get('/modules/install-stream', [ModulesController::class, 'installWithProgress'])->name('modules.install.stream');
        Route::post('/modules/preview', [ModulesController::class, 'previewModule'])->name('modules.preview');
        Route::post('/modules/test-connection', [ModulesController::class, 'testConnection'])->name('modules.test-connection');
        Route::post('/modules/github-token/save', [ModulesController::class, 'saveGithubToken'])->name('modules.github-token.save');
        Route::delete('/modules/github-token', [ModulesController::class, 'clearGithubToken'])->name('modules.github-token.clear');
        Route::get('/modules/deploy-key/check', [ModulesController::class, 'checkDeployKey'])->name('modules.deploy-key.check');
        Route::post('/modules/deploy-key/save', [ModulesController::class, 'saveDeployKey'])->name('modules.deploy-key.save');
        Route::post('/modules/{alias}/enable', [ModulesController::class, 'enable'])->name('modules.enable');
        Route::post('/modules/{alias}/activate', [ModulesController::class, 'enable'])->name('modules.activate'); // Alias for tests
        Route::post('/modules/{alias}/disable', [ModulesController::class, 'disable'])->name('modules.disable');
        Route::delete('/modules/{alias}', [ModulesController::class, 'delete'])->name('modules.delete');
        Route::post('/modules/ajax', [ModulesController::class, 'ajax'])->name('modules.ajax');
    });

    // Themes (admin only for management, but all users can select their theme)
    Route::middleware(['admin'])->group(function () {
        Route::get('/themes', [ThemeController::class, 'index'])->name('themes');
        Route::post('/themes', [ThemeController::class, 'update'])->name('themes.update');
        Route::post('/themes/seed', [ThemeController::class, 'seed'])->name('themes.seed');
        
        // Theme Editor
        Route::get('/themes/editor', [App\Http\Controllers\ThemeEditorController::class, 'index'])->name('themes.editor.index');
        Route::get('/themes/editor/create', [App\Http\Controllers\ThemeEditorController::class, 'create'])->name('themes.editor.create');
        Route::post('/themes/editor', [App\Http\Controllers\ThemeEditorController::class, 'store'])->name('themes.editor.store');
        Route::get('/themes/editor/{theme}', [App\Http\Controllers\ThemeEditorController::class, 'show'])->name('themes.editor.show');
        Route::get('/themes/editor/{theme}/edit', [App\Http\Controllers\ThemeEditorController::class, 'edit'])->name('themes.editor.edit');
        Route::post('/themes/editor/{theme}/update', [App\Http\Controllers\ThemeEditorController::class, 'update'])->name('themes.editor.update');
        Route::delete('/themes/editor/{theme}', [App\Http\Controllers\ThemeEditorController::class, 'destroy'])->name('themes.editor.destroy');
    });

    // Mailbox Permissions
    Route::get('/mailboxes/{mailbox}/permissions', [MailboxController::class, 'permissions'])
        ->name('mailboxes.permissions');
    Route::post('/mailboxes/{mailbox}/permissions', [MailboxController::class, 'updatePermissions'])
        ->name('mailboxes.permissions.update');
    Route::post('/mailboxes/{mailbox}/update-permissions', [MailboxController::class, 'updatePermissions'])
        ->name('mailboxes.update-permissions'); // Alias for tests

    // Mailbox Auto-Reply
    Route::get('/mailboxes/{mailbox}/auto-reply', [MailboxController::class, 'autoReply'])
        ->name('mailboxes.auto_reply');
    Route::get('/mailboxes/{mailbox}/auto-reply-test', [MailboxController::class, 'autoReply'])
        ->name('mailboxes.auto-reply'); // Alias for tests
    Route::post('/mailboxes/{mailbox}/auto-reply', [MailboxController::class, 'saveAutoReply'])
        ->name('mailboxes.auto_reply.save');

    // Test email route
    Route::post('/mailbox/{mailbox}/send-test-email', [MailboxController::class, 'sendTestEmail'])->name('mailboxes.send_test_email');

    // Webhook Gateway
    Route::prefix('admin/webhooks')->name('webhooks.gateway.')->group(function () {
        Route::get('/', [WebhookGatewayController::class, 'index'])->name('index');
        Route::post('/', [WebhookGatewayController::class, 'store'])->name('store');
        Route::get('/{channel}/renew', [WebhookGatewayController::class, 'renewForm'])->name('renew.form');
        Route::post('/{channel}/renew', [WebhookGatewayController::class, 'renew'])->name('renew');
        Route::post('/{channel}/stop', [WebhookGatewayController::class, 'stop'])->name('stop');
        Route::post('/{channel}/test', [WebhookGatewayController::class, 'test'])->name('test');
    });

    // Reconciliation History
    Route::prefix('admin/reconciliation')->name('reconciliation.')->group(function () {
        Route::get('/', [ReconciliationController::class, 'index'])->name('index');
        Route::get('/{run}', [ReconciliationController::class, 'show'])->name('show');
        Route::post('/trigger', [ReconciliationController::class, 'trigger'])->name('trigger');
        Route::post('/discrepancies/{discrepancy}/resolve', [ReconciliationController::class, 'resolve'])->name('discrepancies.resolve');
    });

    // Predictive Analytics
    Route::prefix('admin/analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
    });

    // Milestone Progress Stepper
    Route::prefix('admin/milestones')->name('milestones.')->group(function () {
        Route::get('/', [MilestoneController::class, 'index'])->name('index');
        Route::get('/create', [MilestoneController::class, 'create'])->name('create');
        Route::post('/', [MilestoneController::class, 'store'])->name('store');
        Route::get('/{milestone}', [MilestoneController::class, 'show'])->name('show');
        Route::get('/{milestone}/edit', [MilestoneController::class, 'edit'])->name('edit');
        Route::put('/{milestone}', [MilestoneController::class, 'update'])->name('update');
        Route::delete('/{milestone}', [MilestoneController::class, 'destroy'])->name('destroy');
        Route::post('/{milestone}/progress', [MilestoneController::class, 'updateProgress'])->name('updateProgress');
        Route::post('/{milestone}/status', [MilestoneController::class, 'updateStatus'])->name('updateStatus');
    });

    // Project route aliases (for Dusk tests) - map to milestones controller
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [MilestoneController::class, 'index'])->name('index');
        Route::get('/create', [MilestoneController::class, 'create'])->name('create');
        Route::post('/', [MilestoneController::class, 'store'])->name('store');
        Route::get('/{milestone}', [MilestoneController::class, 'show'])->name('show');
        Route::get('/{milestone}/edit', [MilestoneController::class, 'edit'])->name('edit');
        Route::put('/{milestone}', [MilestoneController::class, 'update'])->name('update');
        Route::delete('/{milestone}', [MilestoneController::class, 'destroy'])->name('destroy');
    });

    Route::post('/customers/ajax', [CustomerController::class, 'ajax'])->name('customers.ajax');
});

Route::middleware('auth')->group(function () {
    // Drafts
    Route::post('/drafts/save', [App\Http\Controllers\DraftController::class, 'save'])->name('drafts.save');
    Route::post('/drafts/discard', [App\Http\Controllers\DraftController::class, 'discard'])->name('drafts.discard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/show', [ProfileController::class, 'edit'])->name('profile.show'); // Alias for tests
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password'); // New route for tests
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Added for tests
    Route::get('/tags/search', [TagController::class, 'ajaxSearch'])->name('tags.ajax_search');
    Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark_as_read');
    
    // Alert Subscriptions (Phase 12.3)
    Route::get('/alerts/subscriptions', [AlertSubscriptionController::class, 'index'])->name('alerts.subscriptions.index');
    Route::post('/alerts/subscriptions', [AlertSubscriptionController::class, 'update'])->name('alerts.subscriptions.update');
    Route::get('/attachments/{id}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::get('/conversations/{id}/print', [ConversationController::class, 'print'])->name('conversations.print');

    // Client 360 Workspace (Accessible to Admins and Techs)
    Route::get('/clients/{client}', [App\Http\Controllers\Admin\Client360Controller::class, 'show'])->name('admin.clients.show');
    
    // CRM Clients Index (Accessible to Admins and Techs)
    Route::get('/crm/clients', [\Modules\Crm\Http\Controllers\ClientController::class, 'index'])->name('admin.crm.clients.index');
});

// API Routes
Route::get('/api/conversations', [ApiConversationController::class, 'index'])
    ->middleware('auth.basic')
    ->name('api.conversations');

// Test Aliases
Route::post('/conversations/{id}/viewing-alias', [CollisionController::class, 'viewing'])->name('collision.viewing')->middleware(['auth', 'verified']);
Route::get('/attachments/{id}/public-download-alias', [PublicAttachmentController::class, 'download'])->name('attachments.public.download');
Route::get('/cron-alias/{hash}', [SystemController::class, 'cron'])->name('cron');
Route::post('/settings/test-smtp-alias', [SettingsController::class, 'testSmtp'])->name('settings.test.smtp')->middleware(['admin']);
Route::post('/settings/test-imap-alias', [SettingsController::class, 'testImap'])->name('settings.test.imap')->middleware(['admin']);
Route::get('/system-alias', [SystemController::class, 'index'])->name('system.index')->middleware(['admin']);
Route::post('/system/failed-jobs/queue/delete-alias', [SystemController::class, 'deleteFailedJobsForQueue'])->name('system.failed-jobs.queue.delete')->middleware(['admin']);
Route::post('/system/failed-jobs/queue/retry-alias', [SystemController::class, 'retryFailedJobsForQueue'])->name('system.failed-jobs.queue.retry')->middleware(['admin']);

// Sentry Testing Route (only available in non-production environments)
if (!app()->environment('production')) {
    Route::get('/test-sentry', function () {
        throw new \Exception('Test exception for Sentry error tracking - this is intentional for testing purposes.');
    })->name('test.sentry')->middleware(['admin']);
}

require __DIR__.'/auth.php';
