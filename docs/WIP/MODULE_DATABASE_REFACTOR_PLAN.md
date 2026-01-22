# Module Database Refactoring Plan

## Goals
- Consolidate "Modify" migrations into "Create" migrations where the table is owned by the same module.
- Eliminate "Fix" migrations by correcting the original definition.
- Standardize naming conventions.
- Verify 100% schema match (or intentional improvement).

## 1. ContractManager Module
### Analysis
- **Base Migration**: `2026_01_16_000001_create_contract_manager_tables.php` creates `cm_quotes`, `cm_quote_line_items`, `cm_quote_revisions`, `cm_contracts`, `cm_contract_schedules`.
- **Modifiers**:
    1. `2026_01_21_000002_add_rejection_columns_to_quotes.php`: Adds `rejection_reason` (text) and `rejected_at` (timestamp) to `cm_quotes`.
    2. `2026_01_21_000003_add_frequency_to_quote_items.php`: Adds `billing_frequency` (enum) and `term_length_months` (int) to `cm_quote_line_items`.
    3. `2026_01_21_170307_add_revision_fields_to_quotes.php`: Adds `revision_of_id` (fk) and `is_current` (bool) to `cm_quotes`.
    4. `2026_01_21_000004_update_billing_template_enums.php`: Modifies `cm_billing_templates` (not in original create?) or applies data patch. Need to check if `cm_billing_templates` exists in base.

### Plan
- [ ] Update `create_contract_manager_tables.php`:
    - Add `rejection_reason` and `rejected_at` to `cm_quotes`.
    - Add `billing_frequency` and `term_length_months` to `cm_quote_line_items`.
    - Add `revision_of_id` and `is_current` to `cm_quotes`.
- [ ] Check `update_billing_template_enums.php`. Use `grep` to see if `cm_billing_templates` is created in base. If so, update enum definition.
- [ ] Delete modifier migrations.

## 2. GoogleAdmin Module
### Analysis
- **Base**: `2026_01_15_000001_create_google_configs_table.php`.
- **Modifier**: `2026_01_20_000001_add_admin_email_to_google_configs_table.php` adds `admin_email`.
### Plan
- [ ] Add `admin_email` to `create_google_configs_table.php`.
- [ ] Delete modifier.

## 3. Payment Module
### Analysis
- **Base**: `2026_01_13_000002_create_payments_table.php`.
- **Modifier**: `2026_01_15_060000_add_dispute_fields_to_payments.php`.
- **Cleanup**: `2026_01_16_000000_remove_balance_from_companies.php`.
### Plan
- [ ] Add dispute fields to `create_payments_table.php`.
- [ ] Verify `companies` table source. If `companies` is core, and `balance` was added by `2026_01_13_000003_add_payment_fields_to_companies_table.php`, then edit that file to NOT add `balance` in the first place, and delete the removal migration.

## 4. PIB Module
### Analysis
- **Fix**: `2026_01_16_000001_fix_client_credit_ledger_table.php`.
- **Modifier**: `2026_01_16_100001_add_company_id_to_pib_tables.php`.
### Plan
- [ ] Merge logic from "Fix" into `create_client_credit_system.php`.
- [ ] Add `company_id` to `service_usage`, `time_entries`, etc. in their respective create files.

## 5. CRM Module
### Analysis
- **Clients**: `2026_01_15_024342_add_crm_fields_to_clients_table.php`.
- **Conversations**: `2026_01_21_030000_add_service_category_to_client_conversations.php`.
### Plan
- [ ] Merge CRM fields into `create_crm_tables.php` (where `clients` is likely defined or extended). *Note: Need to verify if `clients` is Core or CRM.*
- [ ] Merge service category into `create_client_conversations_table.php`.

## Execution Steps
1.  **Backup Modules**: `cp -r Modules Modules_backup`
2.  **Snapshot Schema**: `php artisan schema:dump` (Reference)
3.  **Perform Refactors**: Edit files, delete files.
4.  **Verify**: `db:wipe` -> `migrate` -> `schema:dump` -> `diff`.
