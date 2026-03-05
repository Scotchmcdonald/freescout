# User & Company Lifecycle

## Completed Work
✅ **CRM User Import**: Automated staging data creates records (`CrmStagingRecord`).
✅ **Staging Resolution**: Admin can review and accept staged user configurations.

## Outstanding Work
🔲 **Unify Entities Strategy**: Map identity relationships between `Customer` (Requester) and `ClientUser` (Portal User).
🔲 **Company Creation Validation**: Strict Domain check implementation for automated/manual ingestion.
🔲 **Promote to Client User**: Implement flow to upgrade an "Unknown Sender" (Auto-Customer) to a full Portal User.
🔲 **Status Standardization**: Ensure Enums match across module `is_active` (`ClientUser`) and `status` (`User`).
