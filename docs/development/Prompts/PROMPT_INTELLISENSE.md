Review and resolve PHP IntelliSense/Intelephense errors in this Laravel monorepo by using and refining the existing stub strategy, while keeping the base app file surface minimal.

Primary goal:
- Eliminate real IntelliSense resolution errors with the smallest sustainable set of stub declarations.
- Prefer consolidating fixes into the existing root stub files instead of creating new files.
- Do not add runtime dependencies or autoload these stub files.

Allowed root stub/helper files:
- /var/www/html/_ide_helper_controller_traits.php
- /var/www/html/_ide_helper_models.php
- /var/www/html/_ide_helper_pest.php
- /var/www/html/_ide_helper.php
- /var/www/html/intellisense_vendor_stubs.php
- /var/www/html/intellisense_app_stubs.php

Operating rules:
0. Do not use IntelliSense stubs to mask PHPStan or runtime problems. Only patch genuine language-server symbol resolution gaps.
1. Start by identifying actual current IntelliSense/Intelephense errors, not hypothetical ones - Use the VS Code Problems list or current language-server diagnostics as the source of truth for IntelliSense errors.
2. Classify each error before fixing it:
   - generated-helper issue
   - missing global function
   - missing facade/class alias
   - missing framework/vendor symbol
   - missing trait/controller helper method
   - namespace-specific helper resolution issue
   - false positive that should not be patched
3. Prefer this fix order:
   - First: reuse or extend /var/www/html/intellisense_app_stubs.php for app-level and cross-cutting missing symbols.
   - Second: use /var/www/html/intellisense_vendor_stubs.php for framework/vendor symbols that language servers commonly miss.
   - Third: use /var/www/html/_ide_helper_controller_traits.php only for controller trait method gaps.
   - Fourth: regenerate or minimally adjust generated helper files only if the error clearly belongs there and cannot be handled cleanly in the two hand-maintained stub files.
4. Do not create any new root stub/helper files unless absolutely necessary.
5. Do not scatter one-off stub files through modules or app folders unless there is a hard namespace requirement that cannot be represented cleanly in the existing files.
6. Keep all stub declarations runtime-safe:
   - use if (false) blocks, function_exists guards, or equivalent non-executing patterns
   - do not introduce side effects
   - do not register these files in runtime autoload paths
7. Prefer precise signatures and phpdoc generics when they materially improve IntelliSense.
8. Avoid bloating generated files with hand-written edits unless regeneration is part of the intended workflow.
9. Preserve the distinction between:
   - generated files: _ide_helper*.php
   - hand-maintained files: intellisense_app_stubs.php and intellisense_vendor_stubs.php
10. If a missing symbol comes from Pest, Laravel helpers, Mockery, or common vendor globals, prefer consolidating it into existing shared stubs rather than duplicating declarations.

Specific repo expectations:
- Treat /var/www/html/intellisense_app_stubs.php as the main hand-maintained app stub file.
- Treat /var/www/html/intellisense_vendor_stubs.php as the main hand-maintained framework/vendor fallback file.
- Treat /var/www/html/_ide_helper*.php as generated or generation-aligned files and keep manual edits there to a minimum.
- Keep the total number of maintained root helper/stub files limited to the existing six files listed above.

Required workflow:
1. Inspect current IntelliSense errors.
2. Produce a short triage table with:
   - error
   - source file
   - root cause category
   - proposed target stub file
   - rationale
3. Apply fixes in the smallest number of files possible.
4. Re-check errors after edits.
5. Remove or avoid duplicate declarations when a symbol is already covered elsewhere.
6. If a generated helper file must be changed, explain why the hand-maintained stub files were not sufficient.

Acceptance criteria:
- IntelliSense errors addressed are actually reduced or resolved.
- No unnecessary new stub files are added.
- Fixes are consolidated into the existing six root helper/stub files.
- Hand-maintained declarations remain readable and organized by concern.
- No runtime behavior changes are introduced.
- No broad fake API surface is added beyond what is needed to satisfy IntelliSense.

Output format:
1. Brief summary of the error categories found.
2. Triage table.
3. Exact file changes made and why.
4. Any remaining unresolved IntelliSense errors with reasons.
5. Recommendations to further reduce stub sprawl, if applicable.
