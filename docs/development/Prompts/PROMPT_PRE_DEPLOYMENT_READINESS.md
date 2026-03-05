# Pre-Deployment Readiness Assessment Protocol

**Role:** You are the Lead Release Engineer responsible for verifying the integrity of the FreeScout Modern codebase before deployment.
**Objective:** Execute a rigorous verification process to ensure code quality, architectural compliance, and clean repository state.
**Output:** A structured "Readiness Report" with clear Pass/Fail status for each section and a final Go/No-Go recommendation.

## 1. Automated Test Suite Verification
Execute tests in order of speed/criticality. Stop immediately if critical tests fail.
- [ ] **Unit & Feature Tests**:
  - Command: `./vendor/bin/pest --exclude=arch`
  - *Pass Criteria:* All tests must pass (Green).
- [ ] **Architectural Compliance**:
  - Command: `./vendor/bin/pest tests/ArchTest.php`
  - *Note:* Known issue: `crm core blindness` may fail. If it fails, mark as "WARNING - TECHNICAL DEBT" but do not block unless new violations are introduced.
- [ ] **Static Analysis (PHPStan)**:
  - Command: `./vendor/bin/phpstan analyse --memory-limit=2G`
  - *Pass Criteria:* Zero errors at the current level.

## 2. Git Repository Status
Ensure no uncommitted work or untracked files are accidentally included or excluded.
- [ ] **Main Repository**:
  - Command: `git status --porcelain`
  - *Expectation:* Empty output (clean working directory).
- [ ] **Module Sub-Repositories**:
  - Iterate through all declared modules in `modules_statuses.json` or `Modules/` directory.
  - Check if any module has uncommitted changes or unpushed commits.
  - Validation Command:
    ```bash
    for d in Modules/*; do 
      if [ -d "$d/.git" ]; then 
        echo "Checking $d..."; 
        (cd "$d" && git status --porcelain); 
      fi; 
    done
    ```

## 3. Code Quality & Formatting
- [ ] **Code Style (Pint)**:
  - Command: `./vendor/bin/pint --test`
  - *Action:* If failed, run `./vendor/bin/pint` to fix automatically before committing.
- [ ] **Debug Artifact Cleanup**:
  - Scan for forbidden debugging functions left in code (`dd()`, `dump()`, `ray()`, `var_dump()`).
  - Command: `grep -rnE "dd\(|dump\(|ray\(|var_dump\(" app Modules | grep -v "Tests/"`
  - *Pass Criteria:* No matches in production code.
- [ ] **Temporary File Cleanup**:
  - Ensure no logs or temp files are present.
  - Check: `storage/logs/*.log`, `test_results.txt`, `.DS_Store`.

## 4. Documentation & Configuration Consistency
- [ ] **Environment Template**:
  - Verify `.env.example` contains all keys present in `.env` (excluding secrets).
- [ ] **Documentation Index**:
  - Check `DOCUMENTATION_INDEX.md` is up to date with `docs/` structure.
- [ ] **Database Migrations**:
  - Verify all migrations are valid.
  - Command: `php artisan migrate:status`
  - *Pass Criteria:* All migrations are either "Ran" or ready to run (no "File not found").

## 5. Final Report Structure
Produce a boolean report for the deployment pipeline:

```markdown
# Deployment Readiness Report
**Date:** [YYYY-MM-DD]
**Status:** [GO / NO-GO]

| Check | Status | Notes |
|-------|--------|-------|
| Automated Tests | [PASS/FAIL] | |
| Architecture | [PASS/WARN] | |
| Static Analysis | [PASS/FAIL] | |
| Git Status | [CLEAN/DIRTY] | |
| Debug Cleanup | [CLEAN/DIRTY] | |

**Blockers:**
- [List any blocking issues here]

**Warnings:**
- [List non-blocking warnings here]
```
