# Asset Lifecycle & Staging Process (WIP)

## Completed Work
✅ **CRM User Staging Logic**: CRM always stages incoming users with `CrmStagingRecord` (nullable `customer_id` for "New/Unmapped" users).
✅ **Billing/Update Support**: Users and external signal synchronization now operates via `pending_review` staging records.

## Outstanding Work
🔲 **AssetManagement Alignment**: Align Asset discovery logic with new CRM User logic: **Always Stage**.
🔲 If No Match: Create Staging Record (Type: `Potential New`) instead of auto-creating.
🔲 **Schema Refactor**: Refactor `AssetStagingRecord` to include `resolution_type` ("create", "merge", "ignore") and target mapping (nullable `asset_id`).
🔲 **Resolution Support**: Allow Admins to map "Potential New" asset staging records to existing manual assets.

## Benefits
*   **Duplicate Prevention**: Prevents "Ghost" assets where one device exists as both a Manual entry and a Google-synced entry.
*   **Billing Accuracy**: Ensures every active asset is deliberately approved.
*   **Data Integrity**: Prevents external sources from overwriting critical manual overrides without human check.
