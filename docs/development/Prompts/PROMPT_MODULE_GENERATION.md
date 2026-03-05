# AI Module Generation Prompt

This document provides a standardized prompt to be used with LLMs (like GPT-4, Claude 3, etc.) when generating code for new System Modules. It encapsulates our strict architectural and design standards.

## Usage Guide

1. **Copy the Prompt Pattern** below.
2. **Fill in the [Placeholders]** with your specific module requirements.
3. **Paste** into your LLM interface.

---

## The Prompt

```markdown
You are an Expert System Architect & UI/UX Designer working on the Freescout Service ecosystem (Laravel 12, PHP 8.2+).
Your task is to design and scaffold a new Module: **[MODULE NAME]**.

### 1. Context & Resources
- **Reference Module**: `Modules/EmailMigration` is the "Gold Standard" for UI/UX and structure. Analyze it if available.
- **Documentation**:
  - `docs/development/UX_STYLE_GUIDE.md` (The "Pilot's Cockpit" philosophy).
  - `docs/architecture/ARCHITECTURE_OVERVIEW.md` (Core Blindness, Data Ownership).

### 2. Architectural Constraints (STRICT)
- **Core Blindness**: The Core application MUST NOT depend on this module.
  - ❌ NEVER modify core models (`User`, `Conversation`) or core tables.
  - ✅ Use `ServiceProvider` to register relationships dynamically (e.g., `Client::resolveRelationUsing`).
  - ✅ Listen for Core Events to trigger module logic.
- **Data Ownership**:
  - Store data in module-specific tables (e.g., `[module_prefix]_records`).
  - Do not pollute Core tables with module columns.
- **Service Layer**:
  - Complex logic belongs in `Services/`, not Controllers.
  - Controllers should be thin "Traffic Cops".

### 3. UX/UI Design Standards ("The Pilot's Cockpit")
- **Visual Style**: Clinical, Precise, Information-Dense.
- **Technology**: Tailwind CSS + Alpine.js (`x-data` for state).
- **Key Patterns**:
  - **"The Control Tower"**: Dashboards must show critical metrics, active operations (tables), and health status.
  - **"Guided Journey"**: Use Steppers/Wizards for complex setups. DO NOT use long scrolling forms.
  - **"Troubleshooting Cards"**: specific UI component for errors (What happened? Why? What now?).
  - **Tabs**: Use Alpine.js powered tabs for related content to reduce page loads.
- **Navigation**:
  - "Flight Deck" / "Dashboard" is the entry point.
  - Use breadcrumbs for deep navigation.

### 4. Implementation Requirements
Generate the following files for the **[MODULE NAME]** module:

1.  **Directory Structure**: `Modules/[ModuleName]/...`
2.  **`module.json`**: Module manifest with permissions.
3.  **`Database/Migrations/`**: Complete schema (foreign keys to Core tables allowed, but no changing Core tables).
4.  **`Models/`**: Eloquent models with proper relationships.
5.  **`Providers/[ModuleName]ServiceProvider.php`**:
    - Register Views (`resources/views`).
    - Register Events/Listeners.
    - Inject relationships into Core models dynamically (if needed).
6.  **`Http/Controllers/`**: Controller logic.
7.  **`Resources/views/`**: Blade templates implementing the "Cockpit" UI.

### 5. Specific Feature Requirements
**Module Goal**: [DESCRIBE GOAL, e.g., "Manage recurring software subscriptions for clients"]
**Key Features**:
- [Feature 1, e.g., Import subscriptions from CSV]
- [Feature 2, e.g., Dashboard showing expiring licenses]
- [Feature 3, e.g., Automated renewal email alerts]

**Output**: Provide the code for the essential files listed above, ensuring strict adherence to the "Core Blindness" principle.
```
