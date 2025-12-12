<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CollisionController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
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
use App\Http\Controllers\Api\ConversationController as ApiConversationController;
use App\Http\Controllers\PublicAttachmentController;
use App\Http\Controllers\TrackingController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Public Attachment Download
Route::get('/attachments/{id}/public-download', [PublicAttachmentController::class, 'download'])->name('attachments.public_download');

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

Route::middleware(['auth', 'verified'])->group(function () {
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
        Route::get('/settings/alerts', [SettingsController::class, 'alerts'])->name('settings.alerts');
        Route::put('/settings/alerts', [SettingsController::class, 'updateAlerts'])->name('settings.alerts.update');
        Route::get('/settings/system', [SettingsController::class, 'system'])->name('settings.system');
        Route::post('/settings/cache/clear', [SettingsController::class, 'clearCache'])->name('settings.cache.clear');
        Route::post('/settings/cache/clear-alias', [SettingsController::class, 'clearCache'])->name('system.clear-cache'); // Alias for tests
        Route::post('/settings/migrate', [SettingsController::class, 'migrate'])->name('settings.migrate');
        Route::post('/settings/test-smtp', [SettingsController::class, 'testSmtp'])->name('settings.test-smtp');
        Route::post('/settings/test-imap', [SettingsController::class, 'testImap'])->name('settings.test-imap');
        Route::post('/settings/validate-smtp', [SettingsController::class, 'validateSmtp'])->name('settings.validate-smtp');
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

    // Modules (admin only)
    Route::middleware(['admin'])->group(function () {
        Route::get('/modules/list', [ModulesController::class, 'index'])->name('modules');
        Route::post('/modules/{alias}/enable', [ModulesController::class, 'enable'])->name('modules.enable');
        Route::post('/modules/{alias}/activate', [ModulesController::class, 'enable'])->name('modules.activate'); // Alias for tests
        Route::post('/modules/{alias}/disable', [ModulesController::class, 'disable'])->name('modules.disable');
        Route::delete('/modules/{alias}', [ModulesController::class, 'delete'])->name('modules.delete');
        Route::post('/modules/install', [ModulesController::class, 'install'])->name('modules.install');
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
    Route::get('/attachments/{id}/download', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::get('/conversations/{id}/print', [ConversationController::class, 'print'])->name('conversations.print');
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

require __DIR__.'/auth.php';
