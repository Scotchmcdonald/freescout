# Database Schema Parity Maintenance Guide

## Overview

This document maintains the mapping between the archived (Laravel 5.5) database schema and the modernized (Laravel 11) database schema. It tracks schema evolution, critical differences, and ensures data compatibility during the migration process.

**Last Updated:** November 6, 2025  
**Archive Base Version:** Laravel 5.5.40  
**Current Version:** Laravel 11.0  
**Migration Count:** Archive (73 migrations) → Modern (6 consolidated migrations)

---

## Schema Consolidation Strategy

The modern application consolidates 73+ incremental migrations into 6 comprehensive migration files:

1. `0001_01_01_000000_create_users_table.php` - Users, authentication
2. `0002_01_01_000000_create_mailboxes_tables.php` - Mailboxes, permissions
3. `0003_01_01_000000_create_customers_tables.php` - Customers, emails
4. `0004_01_01_000000_create_conversations_tables.php` - Conversations, threads
5. `0005_01_01_000000_create_attachments_and_logs_tables.php` - Files, logs
6. `0006_01_01_000000_create_system_tables.php` - Options, modules, system

---

## Critical Schema Differences

### 1. Users Table

#### Differences from Archive:
```diff
# Modern (Laravel 11)
+ email_verified_at (timestamp) - Laravel 11 email verification
+ permissions (text) - Moved from env-based to database storage
+ locked (boolean) - Added for account security

# Archive had these added via migrations:
+ locale (via 2018_11_26_122617)
+ status (via 2018_12_11_130728) 
+ permissions (via 2020_12_22_080000)
```

#### Role Constants Mapping:
```php
// Archive (App\User)
ROLE_USER = 2
ROLE_ADMIN = 1

// Modern (App\Models\User) - CORRECTED
ROLE_USER = 1  
ROLE_ADMIN = 2

// ⚠️ CRITICAL: Role values were inverted during modernization
```

#### Data Type Changes:
- `id`: `increments()` → `id()` (same output, different API)
- `email`: `string(191)` → `string(191)` (maintained for compatibility)

---

### 2. Mailboxes Table

#### Archive Evolution (10+ migrations):
```
2018_06_25 - Initial table
2019_06_16 - add in_validate_cert
2019_07_05 - add in_imap_folders
2019_10_06 - add auto_bcc
2019_12_10 - add before_reply
2019_12_22 - change password types
2020_04_16 - add imap_sent_folder
2021_02_17 - change string columns (text fields)
2021_05_21 - encrypt out_password
2023_05_09 - add aliases_reply
2023_11_14 - change aliases (string to text)
2025_01_30 - change imap_sent_folder (string to text)
2025_10_06 - change out_username (string to text)
```

#### Modern Consolidated Schema:
```php
// All evolutions consolidated into single migration
$table->text('out_username')->nullable();      // Final state
$table->text('out_password')->nullable();      // Encrypted
$table->text('in_imap_folders')->nullable();   // Final state
$table->text('imap_sent_folder')->nullable();  // Final state
$table->text('aliases')->nullable();           // Final state (changed from string)
$table->boolean('aliases_reply')->default(false); // Added
$table->boolean('in_validate_cert')->default(true); // Added
$table->string('auto_bcc')->nullable();        // Added
$table->text('before_reply')->nullable();      // Added
```

#### Enum-like Integer Fields:
```php
// These fields use integers to represent enum values
from_name:       1=mailbox, 2=user, 3=custom
out_method:      1=PHP mail, 2=Sendmail, 3=SMTP
in_protocol:     1=IMAP, 2=POP3
in_encryption:   0=none, 1=SSL, 2=TLS
out_encryption:  0=none, 1=SSL, 2=TLS
ticket_status:   1=active, 2=pending, 3=closed
ticket_assignee: 1=anyone, 2=unassigned
template:        1=default
```

---

### 3. Customers Table

#### Archive Evolution:
```
2018_07_09 - Initial table
2020_11_19 - update customers table (structure changes)
2021_04_15 - add meta column
2022_12_25 - set numeric phones (data transformation)
```

#### Modern Schema Notes:
```php
// JSON fields for flexibility
$table->json('phones')->nullable();
$table->json('websites')->nullable(); 
$table->json('social_profiles')->nullable();
$table->text('meta')->nullable();

// Archive used comma-separated or serialized data
// Modern uses proper JSON for better querying
```

---

### 4. Conversations Table

#### Archive Evolution:
```
2018_07_11 - Initial table
2020_06_26 - add email_history column
2021_09_21 - add indexes (performance)
2022_12_17 - add meta column
2025_09_06 - add index (last_reply_at, mailbox_id)
```

#### Modern Indexes:
```php
// Critical indexes maintained:
$table->index('status');
$table->index('state'); 
$table->index('mailbox_id');
$table->index('user_id');
$table->index('customer_id');
$table->index(['last_reply_at', 'mailbox_id']); // Composite for performance
```

---

### 5. Threads Table

#### Archive Evolution:
```
2018_07_12 - Initial table
2018_12_15 - add send_status_data
2019_06_21 - add meta, subtype columns
2020_12_30 - add imported column
2024_06_18 - add index (conversation_id, type, created_by_user_id)
```

#### Modern Schema:
```php
$table->text('send_status_data')->nullable();
$table->text('meta')->nullable();
$table->unsignedTinyInteger('subtype')->nullable();
$table->boolean('imported')->default(false);

// Critical composite index for queries
$table->index(['conversation_id', 'type', 'created_by_user_id']);
```

---

### 6. Mailbox_User Pivot Table

#### Archive Evolution:
```
2018_06_29 - Initial table
2020_02_06 - add hide column
2020_02_16 - add mute column  
2020_09_18 - add access column (permissions)
```

#### Modern Permission Levels:
```php
$table->unsignedTinyInteger('access')->default(10);
// 10 = View only
// 20 = View and reply
// 30 = Full admin access

$table->boolean('after_send')->default(true);
$table->boolean('hide')->default(false);      // Added from evolution
$table->boolean('mute')->default(false);      // Added from evolution
```

---

### 7. Attachments Table

#### Archive Evolution:
```
2018_08_04 - Initial table
2020_03_06 - add public column
```

#### Modern Schema:
```php
$table->boolean('public')->default(false); // For shared/public attachments
// Note: Public attachments marked as security risk in feature parity
```

---

### 8. Send_Logs Table

#### Archive Evolution:
```
2018_08_06 - Initial table
2019_06_25 - change status_message (string to text)
2023_09_05 - add smtp_queue_id
```

#### Modern Schema:
```php
$table->text('status_message')->nullable(); // Changed from string
$table->string('smtp_queue_id', 191)->nullable()->index(); // For tracking
```

---

### 9. Customer_Channel Table

#### Archive Evolution:
```
2023_08_19 - create customer_channel_table
2023_08_19 - populate_customer_channel_table (data migration)
2023_08_29 - add_id_column (structure change after data)
```

#### Modern Implementation:
```php
// Consolidated into single migration with proper ID column from start
$table->id();
$table->foreignId('customer_id')->constrained()->cascadeOnDelete();
$table->unsignedInteger('channel')->index();
$table->string('channel_id', 255)->nullable();
```

---

## Tables Present in Archive but Missing in Modern

### Removed Tables (Intentional):
None identified - all essential tables maintained

### Additional Archive Tables:
- `ltm_translations` - Translation management (marked unnecessary)
- `polycast_events` - Event broadcasting (deprecated in Laravel 11)

---

## Data Migration Considerations

### 1. User Roles - CRITICAL
```php
// Archive to Modern mapping
if ($user->role === 1) {
    // Archive: Admin
    $modernUser->role = 2; // Modern: Admin
} else if ($user->role === 2) {
    // Archive: User  
    $modernUser->role = 1; // Modern: User
}
```

### 2. Password Encryption
```php
// Both use Laravel's encrypt() helper
// Archive: encrypt($password)
// Modern: encrypt($password)
// Compatible, no changes needed
```

### 3. JSON Fields
```php
// Archive: Sometimes stored as serialized or comma-separated
// Modern: Always stored as proper JSON

// Migration example:
$customers = Customer::all();
foreach ($customers as $customer) {
    if (is_string($customer->phones)) {
        $customer->phones = json_decode($customer->phones, true);
        $customer->save();
    }
}
```

### 4. Enum-like Integer Fields
```php
// NO CHANGES - Both use same integer mappings
// Mailbox protocols, encryption types, etc. all compatible
```

---

## Index Strategy

### Archive Approach:
- Indexes added incrementally via separate migrations
- Performance optimizations over time

### Modern Approach:
- All critical indexes defined upfront
- Based on lessons learned from archive

### Critical Indexes in Modern:
```sql
-- Users
INDEX (role), INDEX (status)

-- Mailboxes  
INDEX (status), INDEX (email)

-- Conversations
INDEX (status), INDEX (state), INDEX (mailbox_id)
INDEX (user_id), INDEX (customer_id)
INDEX (last_reply_at, mailbox_id) -- Composite

-- Threads
INDEX (conversation_id), INDEX (user_id), INDEX (type)
INDEX (conversation_id, type, created_by_user_id) -- Composite

-- Folders
INDEX (mailbox_id, type), INDEX (user_id)

-- Send_Logs
INDEX (thread_id), INDEX (smtp_queue_id)
```

---

## Foreign Key Consistency

### Archive Evolution:
```
2020_11_04 - change_foreign_keys_types
// Changed some foreign keys from unsigned int to bigint
```

### Modern Standard:
```php
// All foreign keys use foreignId() which creates bigint unsigned
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
$table->foreignId('mailbox_id')->constrained()->cascadeOnDelete();

// Consistent across all tables
```

---

## Data Type Standards

### String Lengths:
```php
// Critical fields maintained at archive lengths
email: 191 characters (MySQL index limit compatibility)
name fields: 40-255 characters  
hash fields: 100 characters

// Text fields for unlimited content
body, message, meta: text
large content: longText
```

### Boolean Defaults:
```php
// Maintained from archive
enable_kb_shortcuts: true
after_send: true
in_validate_cert: true
locked: false
imported: false
```

---

## Testing Schema Parity

### Verification Queries:

```sql
-- Check table structure
DESCRIBE users;
DESCRIBE mailboxes;
DESCRIBE conversations;

-- Verify indexes
SHOW INDEX FROM conversations;
SHOW INDEX FROM threads;

-- Check foreign keys
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_SCHEMA = 'freescout'
    AND TABLE_NAME = 'conversations';
```

### Laravel Verification:

```php
// Run in tinker
use Illuminate\Support\Facades\Schema;

// Check column exists
Schema::hasColumn('users', 'role');

// Get column type
Schema::getColumnType('users', 'role');

// List all columns
Schema::getColumnListing('mailboxes');

// Check indexes
$indexes = Schema::getConnection()
    ->getDoctrineSchemaManager()
    ->listTableIndexes('conversations');
```

---

## Migration Path

### From Archive to Modern:

```bash
# 1. Export archive data
php artisan export:database --output=archive_data.sql

# 2. Transform data
php artisan migrate:transform-archive --input=archive_data.sql

# 3. Import to modern
php artisan migrate:fresh
php artisan import:transformed-data

# 4. Verify parity
php artisan verify:schema-parity
```

---

## Maintenance Checklist

### When Adding New Features:

- [ ] Check if feature existed in archive
- [ ] Review archive migrations for column history
- [ ] Maintain enum integer mappings
- [ ] Add indexes based on query patterns
- [ ] Document any schema deviations
- [ ] Update this document

### When Modifying Existing Tables:

- [ ] Check archive migration history
- [ ] Ensure backward compatibility with data
- [ ] Maintain foreign key relationships
- [ ] Update related models
- [ ] Create data migration if needed
- [ ] Test with archive data export

---

## Known Deviations

### 1. User Roles
**Status:** Fixed  
**Impact:** Critical  
**Resolution:** Inverted constants corrected, factory updated

### 2. Translation System
**Status:** Accepted deviation  
**Impact:** Low  
**Reason:** Modern apps use external services (Crowdin, Lokalise)

### 3. Polycast Events
**Status:** Removed  
**Impact:** None  
**Reason:** Laravel 11 uses different broadcasting approach

---

## Emergency Recovery

### If Schema Mismatch Detected:

```bash
# 1. Stop application
php artisan down

# 2. Backup current state  
php artisan backup:database

# 3. Compare schemas
php artisan schema:compare --archive=archive/

# 4. Generate fix migration
php artisan make:migration fix_schema_parity_issue

# 5. Review and apply
php artisan migrate

# 6. Verify
php artisan verify:schema-parity

# 7. Resume
php artisan up
```

---

## References

- Archive migrations: `/var/www/html/archive/database/migrations/`
- Modern migrations: `/var/www/html/database/migrations/`
- Models: `/var/www/html/app/Models/`
- Feature Parity: `/var/www/html/docs/FEATURE_PARITY_ANALYSIS.md`

---

## Version History

| Date | Changes | Author |
|------|---------|--------|
| 2025-11-06 | Initial database parity documentation | AI Assistant |
| 2025-11-06 | Fixed user role constants inversion | AI Assistant |
| 2025-11-06 | Consolidated 73 migrations into 6 | Migration Team |

---

## Contact

For schema questions or discrepancies, review this document first, then consult:
1. Feature Parity Analysis (`docs/FEATURE_PARITY_ANALYSIS.md`)
2. Implementation Quick Reference (`docs/IMPLEMENTATION_QUICK_REFERENCE.md`)
3. Migration files directly
