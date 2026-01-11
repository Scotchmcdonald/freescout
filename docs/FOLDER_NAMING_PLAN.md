# Folder Naming Standardization Plan

## 1. Objective
Align folder naming conventions across the project to eliminate inconsistencies and follow a predictable structure. This plan targets the "Root Directory Lowercase" and "Code Subdirectory PascalCase" alignment, which is the most robust standard for Laravel applications.

## 2. Current Inconsistencies
The application currently exhibits mixed casing conventions in high-level directories:

*   **Root Level:**
    *   `app/`, `config/`, `resources/` (Lowercase)
    *   `Modules/` (PascalCase) - **Inconsistent**
*   **Tests Directory:**
    *   `tests/Feature`, `tests/Unit` (PascalCase)
    *   `tests/javascript` (Lowercase) - **Inconsistent**

## 3. Proposed Naming Standard

### Root Directories: All Lowercase
Root directories should be **lowercase** to match the framework standard (`app`, `bootstrap`, `config`, `public`, `vendor`).

*   **Change:** `Modules/` &rarr; `modules/`

### Subdirectories (PSR-4 Code): PascalCase
Directories that map to PHP Namespaces (PSR-4) should be **PascalCase**.

*   `app/Http`, `app/Models` (Already compliant)
*   `modules/Billing`, `modules/Crm` (Already compliant if `modules` is renamed)
*   `tests/Feature`, `tests/Unit` (Already compliant)
*   **Change:** `tests/javascript` &rarr; `tests/JavaScript` (to match sibling directories)

### Subdirectories (Assets/Config): Lowercase
Directories containing config, views, or assets should be **lowercase**.

*   `resources/views`, `resources/css` (Already compliant)
*   `config/` (Already compliant)

## 4. Implementation Plan

### Phase 1: Preparation
1.  **Stop Development:** Ensure no active branches are modifying files within `Modules/`.
2.  **Backup:** Ensure a fresh backup or clean git state.

### Phase 2: Root Directory Alignment (`Modules` -> `modules`)

1.  **Git Rename:** Use `git mv` to ensure case change is tracked on all file systems.
    ```bash
    git mv Modules modules
    ```

2.  **Update `composer.json`:**
    Update the PSR-4 autoload mapping to look in the new lowercase folder.
    ```json
    "autoload": {
        "psr-4": {
            "Modules\\": "modules/"
        }
    }
    ```

3.  **Update `config/modules.php`:**
    Configure the modules package to check the new path.
    ```php
    // config/modules.php
    'paths' => [
        'modules' => base_path('modules'),
        // ...
    ],
    // Verify 'namespace' remains 'Modules', as PHP namespace logic is separate from folder path.
    ```

4.  **Update Tooling Configuration:**
    *   **`phpstan.neon`**: Change `Modules` to `modules` in `paths`.
    *   **`phpunit.xml`**: Check if any test suites explicitly reference `Modules` (usually they reference `tests/`).
    *   **CI/CD Scripts**: Check `deployment/` scripts for hardcoded `Modules/` references.

5.  **Regenerate Autoload:**
    ```bash
    composer dump-autoload
    ```

### Phase 3: Test Directory Alignment (`tests/javascript` -> `tests/JavaScript`)

1.  **Git Rename:**
    ```bash
    git mv tests/javascript tests/JavaScript
    ```

2.  **Update `vitest.config.js`:**
    Update the include pattern.
    ```javascript
    include: ['resources/js/**/*.test.js', 'tests/JavaScript/**/*.test.js']
    ```

### Phase 4: Verification

1.  **Run PHP Tests:** `php artisan test` (Verify Modules are loaded).
2.  **Run JS Tests:** `npm run test` (Verify Vitest finds new path).
3.  **Static Analysis:** `./vendor/bin/phpstan analyse` (Verify paths are valid).

## 5. Risks & Mitigation
*   **Git Case Sensitivity:** On macOS/Windows, `Modules` and `modules` are the same. Using `git mv` is critical.
*   **Hardcoded Strings:** Use `grep -r "Modules/" .` to find any manual string references in documentation or scripts.

## 6. Why Mixed Casing? (Best Practice Rationale)

You mentioned surprise at the "Lowercase Root / PascalCase Subdirectory" approach. This is the **standard, robust design** for modern PHP/Laravel applications for two reasons:

1.  **Framework Convention (Lowercase Roots):**
    Laravel, Rails, and Node projects traditionally use lowercase for structural roots (`app`, `config`, `public`, `vendor`). Using `modules` (lowercase) aligns your custom code with the framework's own directory structure.

2.  **PSR-4 Autoloading (PascalCase Subdirectories):**
    PHP Standards (PSR-4) **require** that the file path matching a sub-namespace matches the case of that namespace.
    *   **Namespace:** `App\Services\BillingService`
    *   **Required Path:** `app/Services/BillingService.php`
    *   **Incorrect Path:** `app/services/BillingService.php` (Will fail on Linux/Production)

    Since we cannot change PHP Namespaces to lowercase (e.g., `class user` instead of `class User`) without violating coding standards (PSR-1), the directories *must* be PascalCase.

    **The Result:** A reliable hybrid.
    *   `modules/` (Structural Root -> Lowercase)
    *   `modules/Billing/` (Namespace `Modules\Billing` -> PascalCase)

## 7. Handling Case Collisions
You raised a concern about `docs` vs `Docs` collisions. While potential, `git mv` handles this well.

**Collision Check Script:**
Before running the rename, run this command to identify any existing conflicts:
```bash
php -r '$d=".";$l=[];foreach(scandir($d)as$f){if($f[0]==".")continue;$k=strtolower($f);if(isset($l[$k]))echo"Collision: {$l[$k]} / $f\n";$l[$k]=$f;}'
```
If a collision is found (e.g., `docs` and `Docs` both exist), the plan is to:
1.  Move contents of the "Wrong" folder (e.g., `Docs`) into the "Right" folder (`docs`).
2.  Remove the empty "Wrong" folder.
3.  Commit.
