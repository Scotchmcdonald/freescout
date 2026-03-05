# Identity, Access, and Company Model (Planned Redesign)

## Overview
Currently, the system uses a fragmented approach to identity, utilizing `App\Models\User` for internal MSP staff, `App\Models\Customer` for passive inbound email senders, and `Modules\Crm\Models\ClientUser` for external client portal access. The term "Client" has become ambiguous, sometimes referring to a business entity and sometimes to a user.

To support true B2B operations and a cohesive portal experience, we are migrating to a Unified Identity Model.

## Requirements & Core Concepts

### 1. Unified Users (`App\Models\User`)
- All individuals capable of logging in—whether Internal MSP staff or External Client contacts—must exist as a unified `User` in the main database table.
- A user will be classified as Internal or External dynamically based on their **RBAC Role**.
- **Role Scopes:** Roles will define if a user is `internal` (MSP Admin, Technician, Finance) or `external` (Client Admin, Client Finance, Normal User, Prospective Client).
- Authentication will use a single guard instead of separating logic across guards for internal/external users.

### 2. Company Entity as the Parent (`Modules\Crm\Models\Company`)
- The ambiguous term **"Client" is strictly deprecated** in favor of **"Company"** for business entities and **"User"** for people.
- `Modules\Crm\Models\Client` and `Modules\Crm\Models\ClientUser` models and tables are obsolete and will be replaced/migrated.
- **Relationship:** A `Company` has many `Users`. A `User` belongs to one or more `Companies` (via `company_user` pivot table). This supports multi-tenancy for users like external consultants who may manage IT for multiple companies.
   *(Note: Single tenancy is the primary workflow today. Multi-tenancy workflows like dropdown selectors for emails are a future-state user story).*

### 3. User Onboarding & Invitations
- The UI under `/users` will show all App users, filterable by Internal vs. External scope.
- The `users/create` form must allow administrators to:
  - Assign Roles
  - If selecting an External Role, attach the user to an existing Company.
  - Optionally create a New Company inline, automatically prompting for an alternative name if a literal collision occurs.
- Instead of manually defining and sharing passwords, creation includes a checkbox to "Send Invitation Email", enrolling the user via a secure link.
- **Prospective Flow:** New prospective client contacts start with a "Prospective Client" role, which converts to standard external roles automatically through business workflows (e.g., accepting a contract).

### 4. Client Self-Administration
- Users granted an external role of "Client Admin" can manage their own team out of the Client Portal (`Client 360` interface).
- **Constraints:** Client Admins can *only* assign external roles scoped to their company (e.g., standard users, billing contacts). They cannot grant internal MSP roles, and the system must prevent them from removing the last Client Admin for their company.

### 5. Inbound Email Flow (Customer vs. User)
- **Passive Logging:** When emails arrive via IMAP (`ImapService`):
  - If the sender domain maps to a known `Company`, the system will SUGGEST creating a new User under that company, OR updating an existing user (e.g., if a contact changed their name/email).
  - If the domain is unknown, the system suggests a workflow to spin up a new Company and User simultaneously.
  - **No Automatic Creation:** The system will *not* auto-generate full User/Customer records from cold inbound unauthenticated traffic, preventing database pollution and spanning identity gaps seamlessly. Unmapped senders remain simple lightweight strings/stubs on the `Conversation` until linked.

## Impact Analysis (To Do)
- Remove `Modules\Crm\Models\Client`
- Remove `Modules\Crm\Models\ClientUser`
- Refactor `ImapService` to implement the Suggest domain model.
- Refactor RBAC Seeder constraints for External scopes.
- Client Portal Navigation refactor.
