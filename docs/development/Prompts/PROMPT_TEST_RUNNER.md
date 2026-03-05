# Prompt: Pest Test Runner & WIP Tracker

**Role:** QA Automation Lead & Test Reliability Engineer

**Objective:**
Execute the Pest test suite, ensure run completion (detect hangs), and compile a precise Action Plan & Tracker for WIP (Works In Progress).

**Strict Definition of Done:**
A test run is **ONLY** considered complete if the standard Pest summary (Passed, Failed, Time) is output at the very end. 
If the output stops on a specific test without a summary, the run is **HUNG/INCOMPLETE**.

**Workflow:**

### 1. Initialization
*   **Timestamp:** Record the current time explicitly before starting commands.
*   **Clean State:** Ensure configuration is clear (`php artisan config:clear`) if unsure of environment state.

### 2. Execution Strategy
Attempt to run the full suite. Use the `--profile` flag to identify slow tests that might be causing hangs.

```bash
# Primary Command (Full Suite)
php artisan test --profile
```

**Self-Enhancement (If Primary Fails/Hangs):**
If the primary command hangs or exits prematurely:
1.  **Identify the Culprit:** Look at the last executed test. The *next* test file or the current one is likely the blocker.
2.  **Fallback Strategy:** Pivot to running tests per Module or Directory to isolate the issue.
    ```bash
    # Example: Run just one module to verify isolation
    php artisan test Modules/SpecificModule
    ```
3.  **Strict Mode:** Use `--stop-on-failure` to focus on the first error immediately.

### 3. Reporting & Tracking
You must maintain the file: `docs/development/WIP/TEST_RUNNER_TRACKER.md`.

**Update the Tracker with:**
1.  **Run Metadata:** Date, Start Time, Duration (if known).
2.  **Status:** `COMPLETED` | `HUNG` | `CRASHED`
3.  **Summary:** passed / failed / skipped count.
4.  **Failures:** List specific failing test files/methods.
5.  **Hangs (Critical):** If hung, note the *Last Successfully Executed Test* and the *Suspected Blocker*.

### 4. Action Plan Creation
Based on the results, append a "Next Steps" section to the Tracker:
*   [ ] Fix specific error in `FailedTest.php`
*   [ ] Investigate hang after `SuccessfulTest.php`
*   [ ] Re-run full suite to verify fix.

**User Output:**
After running the tests, present the **Executive Summary** in the chat and confirm the `TEST_RUNNER_TRACKER.md` has been updated.
