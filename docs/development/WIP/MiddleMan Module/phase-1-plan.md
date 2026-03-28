# MiddleMan Module — Implementation Plan

## Overview
MiddleMan is a developer/admin-only event observation, interception, and marshalling module for the Freescout Service ecosystem. It provides three subsystems:

1. **Logging** — Record events as they fire in a filterable, sortable dashboard
2. **Intercepting** — Catch events before listeners run, hold in a queue, edit, reorder, fire
3. **Marshalling** — Create events from scratch via reflection-powered forms

## Zero-Impact Architecture

### Performance Strategy
- **Feature Flag**: `config('middleman.enabled')` gates the entire module
- **Custom Dispatcher**: `MiddleManDispatcher extends Illuminate\Events\Dispatcher` — replaces default dispatcher only when enabled
- **Redis Rule Cache**: Active rules stored in Redis/cache, never DB lookups in dispatch path
- **Async Writes**: All logging/interception DB writes pushed to dedicated queue jobs

### Dispatch Flow
```
Event::dispatch(SomeEvent)
  → MiddleManDispatcher::dispatch()
    → Check config('middleman.enabled') — if false, parent::dispatch()
    → Check Redis rule cache for event class
    → If INTERCEPT rule: serialize event → WriteInterceptJob → return null (halt)
    → If LOG rule: serialize event → WriteLogJob → parent::dispatch() (continue)
    → If no rule: parent::dispatch() (zero overhead)
```

## Directory Structure
```
Modules/MiddleMan/
├── module.json
├── Config/
│   └── config.php
├── Database/
│   └── Migrations/
│       ├── create_middleman_logs_table.php
│       ├── create_middleman_intercepts_table.php
│       └── create_middleman_audit_trail_table.php
├── Models/
│   ├── MiddleManLog.php
│   ├── MiddleManIntercept.php
│   └── MiddleManAuditEntry.php
├── Providers/
│   └── MiddleManServiceProvider.php
├── Http/
│   └── Controllers/
│       ├── DashboardController.php
│       ├── LoggingController.php
│       ├── InterceptController.php
│       └── MarshalController.php
├── Services/
│   ├── MiddleManDispatcher.php
│   ├── RuleEngine.php
│   ├── EventSerializer.php
│   └── EventScanner.php
├── Jobs/
│   ├── WriteLogEntryJob.php
│   └── WriteInterceptEntryJob.php
├── Contracts/
│   └── MiddleManLoggable.php
├── Routes/
│   └── web.php
└── resources/
    └── views/
        ├── layouts/
        │   └── master.blade.php
        ├── components/
        │   ├── event-detail-panel.blade.php
        │   ├── status-badge.blade.php
        │   └── troubleshooting-card.blade.php
        ├── dashboard/
        │   └── index.blade.php
        ├── logging/
        │   └── index.blade.php
        ├── intercept/
        │   └── index.blade.php
        └── marshal/
            └── index.blade.php
```

## Database Schema

### middleman_logs
| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Auto-increment |
| event_class | string(255) | FQCN of event |
| event_name | string(255) | Short name |
| payload | json | Serialized payload |
| metadata | json | Timestamp, memory, context |
| fired_at | timestamp | When event originally fired |
| created_at | timestamp | When log was written |

### middleman_intercepts
| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Auto-increment |
| event_class | string(255) | FQCN of event |
| event_name | string(255) | Short name |
| payload | json | Serialized editable payload |
| metadata | json | Context, source info |
| status | enum | pending, fired, discarded |
| sort_order | int | User-defined order |
| intercepted_at | timestamp | When caught |
| fired_at | timestamp nullable | When released |
| fired_by | bigint FK nullable | User who released |
| created_at/updated_at | timestamps | |

### middleman_audit_trail
| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Auto-increment |
| user_id | bigint FK | Who performed action |
| action | string(50) | Action type |
| subject_type | string(255) | Model type |
| subject_id | bigint | Model ID |
| details | json | Action details |
| created_at | timestamp | |

## Implementation Phases
1. Foundation: config, migrations, models, contracts
2. Core Engine: MiddleManDispatcher, RuleEngine, EventSerializer
3. Jobs: WriteLogEntryJob, WriteInterceptEntryJob
4. ServiceProvider: registration, dispatcher swap, permissions
5. Controllers + Routes
6. Views: Master layout, Dashboard, Logging, Intercept, Marshal
7. EventScanner: Reflection-based event discovery for Marshalling

## Status: IN PROGRESS
