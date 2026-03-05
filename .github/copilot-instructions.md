# Terminal Execution & Testing Guidelines

## Mandatory Test Output Redirection
When running large or full tests (Pest, PHPUnit, or Artisan test), you MUST redirect the output to a file. Unless running 5 or fewer test groups, always run to a file.
- **Incorrect:** `php artisan test`
- **Correct:** `php artisan test | tee test-results.log`

## Prohibited Inspection Commands
- **NEVER** use `head` or `tail` to inspect test results or large files. These are restricted in this environment.
- Instead of using `head`/`tail`, assume the user wants to see the output or the file, or use `grep` to find specific failures within the generated log.

## Verification Workflow
Before suggesting a terminal command for testing:
1. Ensure a redirection operator (`>`) is present.
2. Ensure the output filename is descriptive (e.g., `pest-results.txt`).
3. If a pipe (`|`) is used, it must not lead into `head`, `tail`, or `less`.