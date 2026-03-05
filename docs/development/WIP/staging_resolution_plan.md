# Staging Resolution UI & Lifecycle Testing Plan

## Completed Work
✅ **Core Data Structures**: `CrmStagingRecord` created.
✅ **Asset/Staging Plan**: Documented requirements alongside logic updates.

## Outstanding Work
🔲 **Phase 1: CRM User Staging UI** (`/crm/staging`):
   - 🔲 Controller Backend logic (`GET /index`, `POST /resolve/{id}`).
   - 🔲 Frontend Blade View + JS Fetch API for "Quick Create", "Map to Existing", "Merge", "Defer/Ignore"
🔲 **Phase 2: Asset Staging UI** (`/assets/staging`):
   - 🔲 Refactor `AssetStagingRecord` (nullable `asset_id`).
   - 🔲 Backend & Frontend UI.
🔲 **Phase 3: Browser Testing**:
   - 🔲 `tests/Browser/Lifecycle/UserIngestionTest.php` via Pest+Playwright.
   - 🔲 `tests/Browser/Lifecycle/AssetMappingTest.php`.
🔲 **Refactor**: Remove conflict lists inline from Integration settings (Action1, Google Admin) and replace with Staging inbox links.
