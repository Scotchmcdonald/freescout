# Prompt: Migration Refactor & Consolidation

**Role:** Senior Database Architect

**Objective:**
Review existing database migrations and refactor them into a minimal, highly cohesive set of high-quality migrations. The goal is to ensure database integrity, idempotency, and maintainability, especially for modular systems.

**Key Requirements:**

1.  **Idempotency:** 
    *   Migrations must assert the proper database state without failing if tables, columns, or indexes already exist.
    *   Use `Schema::hasTable`, `Schema::hasColumn`, etc., to check before creating or modifying as needed.
    *   **Crucial:** Do not just blindly `dropIfExists` unless explicitly intending to wipe data. The goal is often to converge the schema to the desired state.

2.  **Handling Existing Databases (No Refresh):**
    *   **Goal:** The refactored migrations must run safely on a database that has *already* run the original (dirty) migrations, WITHOUT losing data.
    *   **Logic:** 
        *   If the table exists, compare the *current* schema columns against the *desired* schema columns.
        *   Only add missing columns or indexes. Do not alter existing columns unless strictly necessary for the fix.
        *   *Avoid* purely checking `Schema::hasTable` and exiting. The existing table might be from an older version and missing the columns the refactor aims to guarantee.
        *   **Conflict Resolution:** If a `migrations` table entry exists for the old split files, the new consolidated file will still run (as it has a new name). It must effectively act as a "no-op" or "repair" script on existing data.

3.  **Minimal & Cohesive Set:** 
    *   Consolidate "fix" migrations. If you have `2023_01_01_create_posts.php` and `2023_02_01_add_status_to_posts.php`, and both are part of the "core" installation, merge them into the initial creation migration if possible.

3.  **Unexpected Fields & Drift:** 
    *   Handle unexpected fields gracefully. 
    *   Ensure that migrations account for potential schema drift.
    *   Sanitize inputs if migrations involve seeding data.

4.  **Best Practices:** 
    *   Use descriptive foreign key constraints.
    *   Ensure down methods reverse operations exactly.
    *   Use anonymous migration classes (`return new class extends Migration`).

**Workflow:**

### 1. Analysis
*   Scan `database/migrations` and `Modules/*/database/migrations`.
*   Identify tables that are modified by multiple fragmented migrations.
*   Check for missing idempotency checks (blind `create` calls).

### 2. Refactoring Execution
*   **Squashing:** Combine dispersed schema definitions for a single table into one definitive migration file where appropriate.
*   **Safety Wrapper:** Wrap schema operations in conditional checks:
    ```php
    if (!Schema::hasTable('example')) {
        Schema::create('example', function (Blueprint $table) {
            // ...
        });
    } else {
        // Optional: specific column checks/updates if table exists but might be outdated
        Schema::table('example', function (Blueprint $table) {
             if (!Schema::hasColumn('example', 'new_col')) {
                 $table->string('new_col')->nullable();
             }
        });
    }
    ```

### 3. Verification
*   Run `php artisan migrate:fresh` (in a local safe env) to ensure the new cohesive set builds the schema correctly.
*   Run the migrations against an existing database (if possible/relevant) to verify idempotency (it should pass without errors and without destructive side effects).
