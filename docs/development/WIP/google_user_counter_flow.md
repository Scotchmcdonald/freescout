# Google → CRM → PIB User Counter Flow

## Completed Work
✅ Google sync job (`SyncGoogleUsersJob`)
✅ `GoogleUserSynced` event + DTO
✅ CRM listener (`GoogleUserSyncedListener`)
✅ CRM staging model (`CrmStagingRecord`)
✅ `ContactCreated` and `UserStatusChanged` events + DTOs
✅ Counter read entitlement resolver

## Outstanding Work
🔲 **Gap 1**: `StagingController::createCustomerFromStaging()` does not fire `ContactCreated`. Need to add `event(new ContactCreated(...))` after saving the record.
🔲 **Gap 2**: PIB has no User Counter Listeners.
   - 🔲 Create `Modules/PIB/Listeners/UpdateClientUserCounter.php`
   - 🔲 Create `Modules/PIB/Listeners/DecrementClientUserCounter.php`
   - 🔲 Register listeners in `PIBServiceProvider::boot()`
🔲 **Testing**: Write feature tests covering full flow (staging approval -> counter incremented) and (user deactivated -> counter decremented).
🔲 **Backfill**: Backfill command / seeder for existing client user counts.
