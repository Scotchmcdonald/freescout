# Database Migration Assessment: MySQL to MariaDB

**Date:** December 18, 2025
**Module:** Email Migration (V2.1) & FreeScout Core
**Target Database:** MariaDB (Recommended 10.6+)

## Executive Summary
Moving to MariaDB is a **safe and viable strategy** for both the Email Migration module and the core FreeScout application. The recent V2.1 architectural changes in the module have removed the primary compatibility risk, and the core application follows standard Laravel patterns that are fully compatible.

## Technical Analysis (Email Migration Module)

### 1. JSON Handling & Compatibility
*   **Current Usage:** The module uses `JSON` columns for configuration (`settings`, `source_manifest`) and mapping rules (`source_folders`). These are "Write-Once-Read-Many" or "Full-Update" patterns.
*   **MariaDB Difference:** MariaDB stores JSON as `LONGTEXT` with a `JSON_VALID` constraint.
*   **Impact:** 
    *   **Positive:** Since we do not use partial JSON updates (e.g., `JSON_ARRAY_APPEND` on massive arrays), we avoid the performance penalty where MariaDB would need to rewrite the entire column.
    *   **Mitigation:** The V2.1 refactor moved the high-velocity `processed_message_ids` data from a JSON column to the relational `migration_messages` table. This was the single biggest risk factor, and it has been eliminated.

### 2. Indexing & Limits
*   **Schema Check:** The `migration_messages` table uses a composite unique index.
*   **Requirement:** Ensure the MariaDB server is configured with `innodb_large_prefix=ON` (default in modern versions) and uses `ROW_FORMAT=DYNAMIC`.

## Broader Application Assessment (FreeScout Core)

### 1. Core Schema Compatibility
*   **JSON Usage:** The core application uses `JSON` columns in tables like `conversations` (`cc`, `bcc`), `customers` (`phones`, `social_profiles`), and `saved_searches` (`filters`).
*   **Compatibility:** These are standard "metadata" use cases. Laravel's Eloquent ORM handles the serialization (`$casts = ['cc' => 'array']`) identically for MySQL and MariaDB.
*   **No Blockers:** A scan of `app/` and `database/migrations/` reveals no raw SQL queries using MySQL-specific JSON syntax (e.g., `->>` operator in raw strings) that would break on MariaDB.

### 2. Dependencies
*   **Composer Packages:** The project relies on standard packages (`doctrine/dbal`, `webklex/php-imap`) which are database-agnostic or fully support MariaDB.
*   **Laravel 11:** The framework itself has first-class support for MariaDB (via the `mariadb` driver in `config/database.php`).

### 3. Performance Impact (Core App)
*   **Conversations Table:** This is the busiest table. MariaDB's thread pool will help significantly when multiple support agents are active simultaneously, reducing the per-connection memory overhead.
*   **Full-Text Search:** If the app uses `FULLTEXT` indexes (common in helpdesks), MariaDB's implementation is mature and performant.

## Performance & Resource Impact Analysis

### 1. Storage Usage (Neutral to Positive)
*   **JSON Columns:** MariaDB's text-based JSON compresses very efficiently. Given our usage (configuration data), the difference is negligible.
*   **Overall:** Expect storage footprint to remain roughly the same.

### 2. RAM Usage (Positive)
*   **Thread Handling:** MariaDB's thread pool (available in community edition) is significantly more memory-efficient than MySQL's "one-thread-per-connection" model when handling many concurrent connections.
*   **Impact:** For our "50 concurrent workers" scaling target, MariaDB will likely consume less RAM overhead per connection.

### 3. Performance (Positive)
*   **Write Throughput:** MariaDB's XtraDB/InnoDB engine often benchmarks slightly faster for high-concurrency insert workloads (like our migration jobs).
*   **Complex Queries:** MariaDB's query optimizer is more aggressive and may execute complex joins more efficiently.

## Recommendations

1.  **Version Selection:** Use **MariaDB 10.6 LTS** or newer. This version provides the best balance of JSON compatibility and performance.
2.  **Configuration:** Verify `innodb_default_row_format=dynamic` is set in `my.cnf`.
3.  **Testing:** Run the existing test suite (`php artisan test`) against a local MariaDB container to sign off on the switch.

## Conclusion
The entire stack (Module + Core) is "MariaDB Ready". The move aligns with the high-concurrency goals of the migration module and offers potential RAM savings for the core application.
