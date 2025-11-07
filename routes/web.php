<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MailboxController;
use App\Http\Controllers\ModulesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Mailboxes
    Route::get('/mailboxes', [MailboxController::class, 'index'])->name('mailboxes.index');
    Route::post('/mailboxes', [MailboxController::class, 'store'])->name('mailboxes.store');
    Route::get('/mailbox/{mailbox}', [MailboxController::class, 'show'])->name('mailboxes.view');
    Route::match(['patch', 'put'], '/mailbox/{mailbox}', [MailboxController::class, 'update'])->name('mailboxes.update');
    Route::delete('/mailbox/{mailbox}', [MailboxController::class, 'destroy'])->name('mailboxes.destroy');
    Route::get('/mailbox/{mailbox}/settings', [MailboxController::class, 'settings'])->name('mailboxes.settings');
    Route::get('/mailbox/{mailbox}/connection/incoming', [MailboxController::class, 'connectionIncoming'])->name('mailboxes.connection.incoming');
    Route::post('/mailbox/{mailbox}/connection/incoming', [MailboxController::class, 'saveConnectionIncoming']);
    Route::get('/mailbox/{mailbox}/connection/outgoing', [MailboxController::class, 'connectionOutgoing'])->name('mailboxes.connection.outgoing');
    Route::post('/mailbox/{mailbox}/connection/outgoing', [MailboxController::class, 'saveConnectionOutgoing']);
    Route::post('/mailbox/{mailbox}/fetch-emails', [MailboxController::class, 'fetchEmails'])->name('mailboxes.fetch-emails');
    
    // Conversations
    Route::get('/mailbox/{mailbox}/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversation/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::get('/mailbox/{mailbox}/conversation/create', [ConversationController::class, 'create'])->name('conversations.create');
    Route::post('/mailbox/{mailbox}/conversation', [ConversationController::class, 'store'])->name('conversations.store');
    Route::patch('/conversation/{conversation}', [ConversationController::class, 'update'])->name('conversations.update');
    Route::post('/conversation/{conversation}/reply', [ConversationController::class, 'reply'])->name('conversations.reply');
    Route::post('/conversations/ajax', [ConversationController::class, 'ajax'])->name('conversations.ajax');
    Route::delete('/conversation/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');
    Route::get('/conversations/search', [ConversationController::class, 'search'])->name('conversations.search');
    Route::get('/mailbox/{mailbox}/clone-ticket/{thread}', [ConversationController::class, 'clone'])->name('conversations.clone');
    
    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::patch('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::post('/customers/merge', [CustomerController::class, 'merge'])->name('customers.merge');
    
    // Users (admin only)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/user/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/user/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::match(['patch', 'put'], '/user/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/user/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/user/{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions');
    
    // Settings (admin only)
    Route::middleware(['admin'])->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('/settings/email', [SettingsController::class, 'email'])->name('settings.email');
        Route::post('/settings/email', [SettingsController::class, 'updateEmail'])->name('settings.email.update');
        Route::get('/settings/system', [SettingsController::class, 'system'])->name('settings.system');
        Route::post('/settings/cache/clear', [SettingsController::class, 'clearCache'])->name('settings.cache.clear');
        Route::post('/settings/migrate', [SettingsController::class, 'migrate'])->name('settings.migrate');
        Route::post('/settings/test-smtp', [SettingsController::class, 'testSmtp'])->name('settings.test-smtp');
        Route::post('/settings/test-imap', [SettingsController::class, 'testImap'])->name('settings.test-imap');
        Route::post('/settings/validate-smtp', [SettingsController::class, 'validateSmtp'])->name('settings.validate-smtp');
    });
    
    // System (admin only)
    Route::middleware(['admin'])->group(function () {
        Route::get('/system', [SystemController::class, 'index'])->name('system');
        Route::post('/system/ajax', [SystemController::class, 'ajax'])->name('system.ajax');
        Route::get('/system/diagnostics', [SystemController::class, 'diagnostics'])->name('system.diagnostics');
        Route::get('/system/logs', [SystemController::class, 'logs'])->name('system.logs');
    });
    
    // Modules (admin only)
    Route::middleware(['admin'])->group(function () {
        Route::get('/modules', [ModulesController::class, 'index'])->name('modules');
        Route::post('/modules/{alias}/enable', [ModulesController::class, 'enable'])->name('modules.enable');
        Route::post('/modules/{alias}/disable', [ModulesController::class, 'disable'])->name('modules.disable');
        Route::delete('/modules/{alias}', [ModulesController::class, 'delete'])->name('modules.delete');
    });
    
    // Mailbox Permissions
    Route::get('/mailboxes/{mailbox}/permissions', [MailboxController::class, 'permissions'])
        ->name('mailboxes.permissions');
    Route::post('/mailboxes/{mailbox}/permissions', [MailboxController::class, 'updatePermissions'])
        ->name('mailboxes.permissions.update');

    // Mailbox Auto-Reply
    Route::get('/mailboxes/{mailbox}/auto-reply', [MailboxController::class, 'autoReply'])
        ->name('mailboxes.auto_reply');
    Route::post('/mailboxes/{mailbox}/auto-reply', [MailboxController::class, 'saveAutoReply'])
        ->name('mailboxes.auto_reply.save');

    Route::post('/customers/ajax', [CustomerController::class, 'ajax'])->name('customers.ajax');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
