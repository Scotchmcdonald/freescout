# Prompt: Architecture Compliance Fixer

**Role:** Modular Monolith Architecture Expert

**Task:**
Run the architecture compliance suite, analyze violations, and refactor the code to strictly adhere to module isolation and architectural standards.

**Workflow:**

1.  **Run the Analysis:**
    Execute the compliance check script to identify current violations:
    ```bash
    ./scripts/ci/check-architecture-compliance.sh
    ```
    *If that script is not available, run the individual checks located in `scripts/ci/` such as `check-cross-module-imports.sh`.*

    Additionally, run the Pest Architecture tests which enforce stricter rules:
    ```bash
    php artisan test --testsuite=Architecture
    ```
    *(Or: `vendor/bin/pest tests/ArchTest.php`)*

2.  **Analyze Violations:**
    Focus on these critical categories:
    *   **Cross-Module Imports:** Code in `Modules/A` must NEVER directly import classes from `Modules/B` (especially Models).
    *   **Listener Inheritance:** Events listeners must extend the base `IdempotentListener` or equivalent to ensure reliability.
    *   **Core Blindness:** Core services should not depend on specific Modules.

3.  **Apply Fixes (Refactoring Strategy):**
    *   **For Cross-Module Imports:**
        *   Do NOT just move files to hide the error.
        *   **Solution A (Events):** Changes in Module B should be triggered by an Event fired from Module A. Module B listens to Module A's event.
        *   **Solution B (Interfaces):** Use a contract/interface in a shared/core location if direct interaction is strictly necessary (rare).
        *   **Solution C (DTOs):** Pass data via plain arrays or shared DTOs, not Eloquent Models.
    *   **For Inheritance:**
        *   Update the class definition to extend the required base class.
        *   Ensure the `handle` method signature matches the parent.

4.  **Verification:**
    Re-run the script to confirm the violation is gone:
    ```bash
    ./scripts/ci/check-architecture-compliance.sh
    ```

**Strict Constraints:**
*   ❌ **NO** removing checks from the scripts.
*   ❌ **NO** suppressing errors without fixing the root architectural flaw.
*   ✅ **YES** prioritize loose coupling (Event-Driven Architecture).
