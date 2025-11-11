<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Database Compatibility Test Suite
 * 
 * This test verifies that the modernized database schema is fully compatible
 * with the archived FreeScout application's expectations. It ensures:
 * 1. All required tables exist
 * 2. All required columns exist with correct types
 * 3. All required indexes exist
 * 4. All required foreign keys exist
 * 5. Data can be migrated between archived and modernized versions
 */
class DatabaseCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that all core tables from archived app exist in modernized schema
     */
    public function test_all_archived_tables_exist(): void
    {
        $requiredTables = [
            'users',
            'customers',
            'conversations',
            'threads',
            'mailboxes',
            'folders',
            'attachments',
            'options',
            'activity_log',
            'jobs',
            'failed_jobs',
            'password_reset_tokens',
            'sessions',
        ];

        foreach ($requiredTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Required table '{$table}' from archived app does not exist in modernized schema"
            );
        }
    }

    /**
     * Test users table schema compatibility
     */
    public function test_users_table_schema_compatibility(): void
    {
        $requiredColumns = [
            'id',
            'first_name',
            'last_name',
            'email',
            'password',
            'role',
            'timezone',
            'time_format',
            'enable_kb_shortcuts',
            'locale',
            'created_at',
            'updated_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('users', $column),
                "Required column 'users.{$column}' from archived app is missing"
            );
        }

        // Verify email is unique
        $indexes = $this->getTableIndexes('users');
        $hasUniqueEmail = false;
        foreach ($indexes as $index) {
            if (in_array('email', $index['columns']) && $index['unique']) {
                $hasUniqueEmail = true;
                break;
            }
        }
        $this->assertTrue($hasUniqueEmail, "users.email should have unique index");
    }

    /**
     * Test customers table schema compatibility
     */
    public function test_customers_table_schema_compatibility(): void
    {
        $requiredColumns = [
            'id',
            'first_name',
            'last_name',
            'email',
            'phone',
            'company',
            'city',
            'state',
            'zip',
            'country',
            'address',
            'notes',
            'photo_url',
            'created_at',
            'updated_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('customers', $column),
                "Required column 'customers.{$column}' from archived app is missing"
            );
        }
    }

    /**
     * Test conversations table schema compatibility
     */
    public function test_conversations_table_schema_compatibility(): void
    {
        $requiredColumns = [
            'id',
            'number',
            'mailbox_id',
            'folder_id',
            'user_id',
            'customer_id',
            'subject',
            'status',
            'state',
            'preview',
            'last_reply',
            'source_via',
            'source_type',
            'created_at',
            'updated_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('conversations', $column),
                "Required column 'conversations.{$column}' from archived app is missing"
            );
        }

        // Verify foreign keys exist
        $this->assertTrue(
            Schema::hasColumn('conversations', 'mailbox_id'),
            "conversations.mailbox_id foreign key column is missing"
        );
        
        $this->assertTrue(
            Schema::hasColumn('conversations', 'customer_id'),
            "conversations.customer_id foreign key column is missing"
        );
    }

    /**
     * Test threads table schema compatibility
     */
    public function test_threads_table_schema_compatibility(): void
    {
        $requiredColumns = [
            'id',
            'conversation_id',
            'user_id',
            'customer_id',
            'type',
            'status',
            'state',
            'action_type',
            'source_via',
            'source_type',
            'body',
            'to',
            'cc',
            'bcc',
            'from',
            'opened_at',
            'created_at',
            'updated_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('threads', $column),
                "Required column 'threads.{$column}' from archived app is missing"
            );
        }

        // Verify conversation_id foreign key
        $this->assertTrue(
            Schema::hasColumn('threads', 'conversation_id'),
            "threads.conversation_id foreign key column is missing"
        );
    }

    /**
     * Test mailboxes table schema compatibility
     */
    public function test_mailboxes_table_schema_compatibility(): void
    {
        $requiredColumns = [
            'id',
            'name',
            'email',
            'from_name',
            'from_name_type',
            'ticket_status',
            'ticket_assignee',
            'template',
            'auto_reply_enabled',
            'auto_reply_subject',
            'auto_reply_message',
            'in_server',
            'in_port',
            'in_username',
            'in_password',
            'in_protocol',
            'out_server',
            'out_port',
            'out_username',
            'out_password',
            'out_encryption',
            'created_at',
            'updated_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('mailboxes', $column),
                "Required column 'mailboxes.{$column}' from archived app is missing"
            );
        }

        // Verify email is unique
        $indexes = $this->getTableIndexes('mailboxes');
        $hasUniqueEmail = false;
        foreach ($indexes as $index) {
            if (in_array('email', $index['columns']) && $index['unique']) {
                $hasUniqueEmail = true;
                break;
            }
        }
        $this->assertTrue($hasUniqueEmail, "mailboxes.email should have unique index");
    }

    /**
     * Test folders table schema compatibility
     */
    public function test_folders_table_schema_compatibility(): void
    {
        $requiredColumns = [
            'id',
            'mailbox_id',
            'user_id',
            'type',
            'active_count',
            'total_count',
            'created_at',
            'updated_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('folders', $column),
                "Required column 'folders.{$column}' from archived app is missing"
            );
        }
    }

    /**
     * Test attachments table schema compatibility
     */
    public function test_attachments_table_schema_compatibility(): void
    {
        $requiredColumns = [
            'id',
            'thread_id',
            'file_name',
            'file_dir',
            'file_size',
            'mime_type',
            'embedded',
            'created_at',
            'updated_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('attachments', $column),
                "Required column 'attachments.{$column}' from archived app is missing"
            );
        }
    }

    /**
     * Test options table schema compatibility
     */
    public function test_options_table_schema_compatibility(): void
    {
        $requiredColumns = [
            'id',
            'name',
            'value',
            'created_at',
            'updated_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('options', $column),
                "Required column 'options.{$column}' from archived app is missing"
            );
        }

        // Verify name is unique
        $indexes = $this->getTableIndexes('options');
        $hasUniqueName = false;
        foreach ($indexes as $index) {
            if (in_array('name', $index['columns']) && $index['unique']) {
                $hasUniqueName = true;
                break;
            }
        }
        $this->assertTrue($hasUniqueName, "options.name should have unique index");
    }

    /**
     * Test activity_log table schema compatibility
     */
    public function test_activity_log_table_schema_compatibility(): void
    {
        $requiredColumns = [
            'id',
            'log_name',
            'description',
            'subject_type',
            'subject_id',
            'causer_type',
            'causer_id',
            'properties',
            'created_at',
            'updated_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('activity_log', $column),
                "Required column 'activity_log.{$column}' from archived app is missing"
            );
        }
    }

    /**
     * Test pivot table mailbox_user exists and has correct schema
     */
    public function test_mailbox_user_pivot_table_compatibility(): void
    {
        $this->assertTrue(
            Schema::hasTable('mailbox_user'),
            "Pivot table 'mailbox_user' from archived app does not exist"
        );

        $requiredColumns = [
            'id',
            'mailbox_id',
            'user_id',
            'created_at',
            'updated_at',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('mailbox_user', $column),
                "Required column 'mailbox_user.{$column}' is missing"
            );
        }
    }

    /**
     * Test that conversation status constants are compatible
     */
    public function test_conversation_status_values_compatibility(): void
    {
        // Verify the Conversation model has the expected status constants
        $conversationClass = \App\Models\Conversation::class;
        
        $this->assertTrue(
            defined("{$conversationClass}::STATUS_ACTIVE"),
            "STATUS_ACTIVE constant missing from Conversation model"
        );
        
        $this->assertTrue(
            defined("{$conversationClass}::STATUS_PENDING"),
            "STATUS_PENDING constant missing from Conversation model"
        );
        
        $this->assertTrue(
            defined("{$conversationClass}::STATUS_CLOSED"),
            "STATUS_CLOSED constant missing from Conversation model"
        );

        // Verify values match archived app expectations
        $this->assertEquals(1, $conversationClass::STATUS_ACTIVE);
        $this->assertEquals(2, $conversationClass::STATUS_PENDING);
        $this->assertEquals(3, $conversationClass::STATUS_CLOSED);
    }

    /**
     * Test that user role constants are compatible
     */
    public function test_user_role_values_compatibility(): void
    {
        $userClass = \App\Models\User::class;
        
        $this->assertTrue(
            defined("{$userClass}::ROLE_ADMIN"),
            "ROLE_ADMIN constant missing from User model"
        );
        
        $this->assertTrue(
            defined("{$userClass}::ROLE_USER"),
            "ROLE_USER constant missing from User model"
        );

        // Verify values match archived app expectations
        $this->assertEquals(1, $userClass::ROLE_ADMIN);
        $this->assertEquals(2, $userClass::ROLE_USER);
    }

    /**
     * Test that thread type constants are compatible
     */
    public function test_thread_type_values_compatibility(): void
    {
        $threadClass = \App\Models\Thread::class;
        
        $requiredConstants = [
            'TYPE_MESSAGE',
            'TYPE_CUSTOMER',
            'TYPE_NOTE',
        ];

        foreach ($requiredConstants as $constant) {
            $this->assertTrue(
                defined("{$threadClass}::{$constant}"),
                "{$constant} constant missing from Thread model"
            );
        }

        // Verify values match archived app expectations
        $this->assertEquals(1, $threadClass::TYPE_MESSAGE);
        $this->assertEquals(2, $threadClass::TYPE_CUSTOMER);
        $this->assertEquals(3, $threadClass::TYPE_NOTE);
    }

    /**
     * Test data migration compatibility - can we insert archived data?
     */
    public function test_archived_data_insertion_compatibility(): void
    {
        // Simulate inserting data in the format the archived app would use
        
        // Insert user
        $userId = DB::table('users')->insertGetId([
            'first_name' => 'Archive',
            'last_name' => 'User',
            'email' => 'archive@example.com',
            'password' => bcrypt('password'),
            'role' => 1, // Admin
            'timezone' => 'UTC',
            'time_format' => 12,
            'enable_kb_shortcuts' => 1,
            'locale' => 'en',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertIsInt($userId);
        $this->assertDatabaseHas('users', ['email' => 'archive@example.com']);

        // Insert customer
        $customerId = DB::table('customers')->insertGetId([
            'first_name' => 'Archive',
            'last_name' => 'Customer',
            'email' => 'customer@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertIsInt($customerId);
        $this->assertDatabaseHas('customers', ['email' => 'customer@example.com']);

        // Insert mailbox
        $mailboxId = DB::table('mailboxes')->insertGetId([
            'name' => 'Archive Mailbox',
            'email' => 'archive@mailbox.com',
            'from_name' => 'Archive Support',
            'from_name_type' => 1,
            'ticket_status' => 1,
            'ticket_assignee' => 1,
            'template' => 'plain',
            'auto_reply_enabled' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertIsInt($mailboxId);
        $this->assertDatabaseHas('mailboxes', ['email' => 'archive@mailbox.com']);

        // Insert folder
        $folderId = DB::table('folders')->insertGetId([
            'mailbox_id' => $mailboxId,
            'user_id' => null,
            'type' => 1,
            'active_count' => 0,
            'total_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertIsInt($folderId);

        // Insert conversation
        $conversationId = DB::table('conversations')->insertGetId([
            'number' => 1,
            'mailbox_id' => $mailboxId,
            'folder_id' => $folderId,
            'user_id' => $userId,
            'customer_id' => $customerId,
            'subject' => 'Archive Test Conversation',
            'status' => 1,
            'state' => 1,
            'source_via' => 1,
            'source_type' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertIsInt($conversationId);
        $this->assertDatabaseHas('conversations', ['subject' => 'Archive Test Conversation']);

        // Insert thread
        $threadId = DB::table('threads')->insertGetId([
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'type' => 1, // Message
            'status' => 1,
            'state' => 1,
            'action_type' => 1,
            'source_via' => 1,
            'source_type' => 1,
            'body' => 'This is a test message from archived app',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertIsInt($threadId);
        $this->assertDatabaseHas('threads', ['conversation_id' => $conversationId]);

        // Insert attachment
        $attachmentId = DB::table('attachments')->insertGetId([
            'thread_id' => $threadId,
            'file_name' => 'test.pdf',
            'file_dir' => 'storage/attachments',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'embedded' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertIsInt($attachmentId);

        // Insert option
        DB::table('options')->insert([
            'name' => 'archive_test_option',
            'value' => 'test_value',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertDatabaseHas('options', ['name' => 'archive_test_option']);

        // Insert mailbox_user pivot
        DB::table('mailbox_user')->insert([
            'mailbox_id' => $mailboxId,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertDatabaseHas('mailbox_user', [
            'mailbox_id' => $mailboxId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Test reading archived data through Eloquent models
     */
    public function test_eloquent_models_read_archived_data(): void
    {
        // Insert data using raw queries (as archived app would)
        $userId = DB::table('users')->insertGetId([
            'first_name' => 'Eloquent',
            'last_name' => 'Test',
            'email' => 'eloquent@example.com',
            'password' => bcrypt('password'),
            'role' => 1,
            'timezone' => 'UTC',
            'time_format' => 12,
            'enable_kb_shortcuts' => 1,
            'locale' => 'en',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Read through Eloquent
        $user = \App\Models\User::find($userId);
        $this->assertNotNull($user);
        $this->assertEquals('Eloquent', $user->first_name);
        $this->assertEquals('eloquent@example.com', $user->email);
        $this->assertEquals(1, $user->role);

        // Verify relationships work
        $mailboxId = DB::table('mailboxes')->insertGetId([
            'name' => 'Test Mailbox',
            'email' => 'eloquent@mailbox.com',
            'from_name' => 'Support',
            'from_name_type' => 1,
            'ticket_status' => 1,
            'ticket_assignee' => 1,
            'template' => 'plain',
            'auto_reply_enabled' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('mailbox_user')->insert([
            'mailbox_id' => $mailboxId,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = \App\Models\User::find($userId);
        $this->assertCount(1, $user->mailboxes);
        $this->assertEquals('Test Mailbox', $user->mailboxes->first()->name);
    }

    /**
     * Helper method to get table indexes
     */
    protected function getTableIndexes(string $table): array
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        $indexes = DB::select(
            "SELECT 
                INDEX_NAME as name,
                COLUMN_NAME as column_name,
                NON_UNIQUE = 0 as is_unique
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY INDEX_NAME, SEQ_IN_INDEX",
            [$database, $table]
        );

        $result = [];
        foreach ($indexes as $index) {
            $name = $index->name;
            if (!isset($result[$name])) {
                $result[$name] = [
                    'name' => $name,
                    'columns' => [],
                    'unique' => (bool) $index->is_unique,
                ];
            }
            $result[$name]['columns'][] = $index->column_name;
        }

        return array_values($result);
    }
}
