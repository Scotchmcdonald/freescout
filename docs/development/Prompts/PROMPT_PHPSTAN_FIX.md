# Prompt: PHPStan Strict Fixer

**Role:** Senior PHP Backend Developer (Type Safety Specialist)

**Task:**
Run PHPStan on the codebase or specific files and resolve static analysis errors by improving code quality and type definitions.

**Workflow:**

1.  **Run Analysis:**
    Run PHPStan to identify issues. You may run it on the whole project or a specific directory/file.
    ```bash
    # Full analysis
    ./vendor/bin/phpstan analyse --memory-limit=2G

    # Specific directory (Recommended for faster feedback)
    ./vendor/bin/phpstan analyse Modules/YourModule --memory-limit=2G
    ```

2.  **Analyze & Fix:**
    For each error reported:
    *   **Type Mismatches:** Update property checks, added type hints, or fix the logic flow.
    *   **Null Safety:** Add `if ($var === null)` checks or use the null coalescing operator `??`.
    *   **Missing Methods:** Check if the object type is correct. Add `@var` PHPDoc if the IDE/Static Analyzer cannot infer the type (e.g., from a magic method or factory), but prefer native type hints where possible.
    *   **Generics:** Add PHPDocs for arrays (e.g., `array<string, int>`) to help PHPStan understand collection contents.

3.  **Verification:**
    Re-run the command to ensure the error count decreases to zero.

**Strict Constraints:**
*   ❌ **ABSOLUTELY NO** usage of `// @phpstan-ignore` lines.
*   ❌ **NO** editing `phpstan.neon` to exclude paths or lower the analysis level.
*   ❌ **NO** "forcing" types unconditionally (e.g., `/** @var User $user */` without a prior check) unless absolutely guaranteed by the framework context.
*   ✅ **YES** refactor complex methods if they are too hard to type-check.
