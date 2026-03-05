<?php
/**
 * One-shot cleanup: wipe all conversations, threads, emails, customers,
 * CaseManager and Fern records, and the job queues.
 *
 * Run:  php scripts/clear_conversations.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$db = app('db');

$tables = [
    'conversations',
    'threads',
    'emails',
    'attachments',
    'folders',
    'customers',
    'customer_channel',
    'customer_customer_field',
    'channel_customer',
    'case_manager_cases',
    'case_manager_activity_log',
    'case_manager_diagnostics',
    'case_manager_quick_wins',
    'fern_case_records',
    'fern_diagnostics',
    'jobs',
    'failed_jobs',
    'processed_events',
];

$db->statement('SET FOREIGN_KEY_CHECKS=0');

foreach ($tables as $table) {
    try {
        $db->table($table)->truncate();
        echo "✓ Truncated: {$table}\n";
    } catch (\Exception $e) {
        echo "⚠ Skipped {$table}: " . $e->getMessage() . "\n";
    }
}

$db->statement('SET FOREIGN_KEY_CHECKS=1');
echo "\nDone — all conversations and email data cleared.\n";
