# Smart Test Runner Requirements

## Overview
The Smart Test Runner (`scripts/test_runner.php`) is a custom PHP script designed to execute the Laravel application's test suite efficiently. It addresses issues with slow execution and timeouts by implementing intelligent batching, calibration, and recursive recovery strategies.

## Core Features

### 1. Environment Setup
Before executing tests, the runner must ensure the environment is ready:
- **Signal Handling:** Gracefully handles `SIGINT` (Ctrl+C) and `SIGTERM` to terminate any active child processes (e.g., PHPUnit) before exiting.
- **Permissions:** Checks if `$baseDir` is owned by `www-data:www-data` and if `storage/` and `bootstrap/cache/` are writable. Only runs the heavy `sudo chown/chmod/setfacl` commands if these checks fail.
- **Cache Clearing:** Executes `php artisan optimize:clear` after ensuring permissions are correct.

### 2. Test Discovery
- **Search Path:** Scans `tests/` directory for files matching `*Test.php`.
- **New Files:** Detects files present on disk but missing from `runner_config.json`. These are added as individual batches for the current run to ensure they are tested and timed.

### 3. Smart Batching & Execution
- **Goal:** Group tests into batches that run within a specific target duration (default: 20 seconds).
- **Mechanism:** 
  - Uses historical timing data stored in `tests/runner_config.json`.
  - Dynamically groups files to fill the target time window.
  - Ensures single slow files run in their own batch.
- **Timing Accuracy:** Uses `--log-junit` to capture precise execution time for each file within a batch, updating the historical data after every run.

### 4. Recursive Timeout Recovery
- **Problem:** A batch of tests may time out due to environmental issues or specific test interactions.
- **Solution:** 
  - If a batch times out (limit: `max(10s, target * 5)`), the runner catches the `ProcessTimedOutException`.
  - It splits the batch into two smaller halves.
  - It recursively runs these smaller batches.
  - If a single file times out, it is logged to `reports/.../timeout.log`.
  - The split structure is effectively "learned" for the next run via the Rebalancing phase.

### 5. Rebalancing (Self-Optimization)
- **Trigger:** Occurs automatically at the end of every normal run.
- **Process:** 
  - Updates `runner_config.json` with the actual execution times captured during the run.
  - Re-calculates optimal batches based on the target duration.
  - Saves the new configuration, ensuring the next run is more efficient.

### 6. Calibration Mode
- **Flag:** `--calibrate`
- **Behavior:** Runs every test file individually to gather accurate execution times.
- **Output:** Updates `tests/runner_config.json` with fresh timing data and regenerates optimal batches.

### 7. Reporting & Logging
- **Directory:** `reports/test_runs_YYYY-MM-DD_HHMMSS/`
- **Logs:**
  - `error.log`: PHP Fatal errors and Exceptions.
  - `failure.log`: PHPUnit assertion failures.
  - `skipped.log`: Skipped tests.
  - `incomplete.log`: Incomplete tests.
  - `risky.log`: Risky tests.
  - `warnings.log`: PHPUnit warnings and PHP notices/deprecations.
  - `deprecation.log`: Deprecation notices.
  - `timeout.log`: Details of batches or files that timed out.
  - `batch_X.xml`: Temporary JUnit XML files (cleaned up automatically).
- **Summary:** Displays a list of all created files (config, reports) at the end of execution.

### 8. Visual Feedback
- **Progress Bar:** Shows overall progress (batches completed / total). 
- **Status Bar:** Displays a color-coded breakdown of results (Pass, Fail, Error, Skip, etc.) for the current run.
- **Visibility Rule:** 
  - If space permits, every result type with a count > 0 is displayed with at least one character block.
  - If space is insufficient to show all non-zero types, priority is given to the types with the highest counts (highest proportions).
- **Batch Info:** Displays the current batch/file being run below the progress bar, which is cleared upon completion.

## Configuration
- **File:** `tests/runner_config.json`
- **Structure:**
  ```json
  {
      "target_seconds": 20.0,
      "file_times": {
          "path/to/test.php": 0.45
      },
      "batches": [
          ["path/to/test1.php", "path/to/test2.php"]
      ]
  }
  ```
- **Precedence:** CLI arguments (`--target`) override config file values.

## Usage
- **Standard Run:** `php scripts/test_runner.php`
- **Calibrate:** `php scripts/test_runner.php --calibrate`
- **Custom Target:** `php scripts/test_runner.php --target=10.0`

## Exit Codes
- **0:** All tests passed.
- **1:** Failures or Errors occurred.
- **130:** Script interrupted by user (SIGINT).
