# Coverage Uplift to 80% — Infection Best-Practices Target

**Date:** 2026-03-25
**Author:** Agent
**Status:** IN PROGRESS

---

## Why 80%?

Infection mutation testing operates at **maximum effectiveness** when line coverage ≥ 80%. Below that threshold, uncovered code cannot be mutated, creating a blind spot that bypasses the mutation gate entirely. Our current state:

| Metric | Current | Target | Gap |
|:-------|--------:|-------:|----:|
| **PHP source coverage** *(all PHP files, from coverage XML)* | **64.3%** | **≥ 80%** | **−15.7pp** |
| Infection MSI (covered code) | 100% | ≥ 95% | ✅ already met |
| Infection minMsi (global) | 100% | ≥ 95% | ✅ already met |

The MSI numbers are already perfect — but they only measure *already-covered* code. Getting from 64% → 80% exposes ~11,503 more executable lines to mutation, substantially widening the quality guarantee.

> **Note on baselines:** `python3 scripts/testing/parse-coverage-index.py` reads `storage/infection/coverage/index.xml` and produces the canonical table. The numbers include test files in the count (since they are PHP files with executable lines that Infection processes), so the baseline is 64.3%, not 50.9% (which was mis-calculated from a different extraction method).

---

## Current Coverage by Namespace

*(Views/blade excluded. Run `python3 scripts/testing/parse-coverage-index.py` for live data. Sorted by gap.)*

| Namespace | Covered | Executable | % | Gap | Phase |
|:----------|--------:|-----------:|--:|----:|:------|
| `Modules/EmailMigration` | 1,089 | 6,727 | 16.2% | 5,638 | **1** |
| `app/Http` | 3,795 | 7,097 | 53.5% | 3,302 | **3** |
| `Modules/Crm` | 3,015 | 5,085 | 59.3% | 2,070 | **4** |
| `Modules/SoftwareSubscriptions` | 3,002 | 4,823 | 62.2% | 1,821 | **4** |
| `Modules/ContractManager` | 3,149 | 4,687 | 67.2% | 1,538 | **4** |
| `Modules/Action1` | 2,728 | 4,069 | 67.0% | 1,341 | **3** |
| `Modules/PIB` | 5,169 | 6,505 | 79.5% | 1,336 | **4** |
| `Modules/Alerts` | 1,148 | 2,211 | 51.9% | 1,063 | **3** |
| `app/Console` | 486 | 1,336 | 36.4% | 850 | **3** |
| `Modules/AssetManagement` | 871 | 1,700 | 51.2% | 829 | **3** |
| `Modules/KnowledgeBase` | 3,173 | 3,994 | 79.4% | 821 | **4** |
| `Modules/ClientPortal` | 532 | 1,330 | 40.0% | 798 | **2** |
| `Modules/GoogleAdmin` | 1,227 | 1,966 | 62.4% | 739 | **3** |
| `app/Actions` | 115 | 558 | 20.6% | 443 | **2** |
| `app/DataTransferObjects` | 56 | 414 | 13.5% | 358 | **2** |
| `Modules/DeploymentManager` | 300 | 589 | 50.9% | 289 | **2** |
| `app/Widgets` | 172 | 415 | 41.4% | 243 | **2** |
| `Modules/DevFeedback` | 51 | 118 | 43.2% | 67 | **2** |
| *(all others ≥70% or tiny)* | — | — | — | ~300 | — |
| **TOTAL** | **47,277** | **73,475** | **64.3%** | **26,198** | |

---

## Phased Coverage Targets

Each phase is designed to yield a measurable, validated checkpoint.

| Phase | Focus | Lines Added | Running % | Delivered |
|:------|:------|------------:|----------:|:---------:|
| **Baseline** | — | — | 64.3% | ✅ |
| **Phase 1** | EmailMigration (Models → Jobs → Controllers) | +3,500 | ~69.1% | ⬜ |
| **Phase 2** | ClientPortal, DeploymentManager, app/Actions, DTOs, Widgets, DevFeedback | +2,000 | ~71.8% | ⬜ |
| **Phase 3** | Action1, GoogleAdmin, AssetManagement, Alerts, app/Http+Console | +3,500 | ~76.6% | ⬜ |
| **Phase 4** | Crm, SoftwareSubscriptions, ContractManager, PIB, KnowledgeBase (expand) | +3,000 | **≥ 80.7%** ✅ | ⬜ |

> **Phase 4 is the 80% threshold phase** — completing it is the primary goal. Run `parse-coverage-index.py` after each phase to track exact progress.

---

## Methodology Per Phase

1. **Write tests → run suite → verify coverage rises** (use `XDEBUG_MODE=coverage php vendor/bin/pest --coverage-xml=storage/infection/coverage`)
2. **Run `check-mutation-tier2.sh`** — MSI must stay ≥ 95 after each phase
3. **Commit** with `test: Phase N — <description>` message
4. **Update this README** with actual `Running %` achieved

### Test Patterns by Layer

| Layer | Pattern | Base Class |
|:------|:--------|:-----------|
| Models | `RefreshDatabase`, factory create/update/delete, relationship assertions | `IntegrationTestCase` |
| DTOs / Value Objects | Pure construction + validation, no DB | `PureUnitTestCase` |
| Events / Enums | Construction, accessors, no side effects | `PureUnitTestCase` |
| Services (no I/O) | Direct instantiation, pure logic | `PureUnitTestCase` |
| Services (I/O / external API) | Mock client + assert calls dispatched correctly | `PureUnitTestCase` |
| Jobs | `Queue::fake()` + `assertPushed()` or `Bus::fake()`, mock external dependencies | `TestCase` |
| Controllers | `actingAs()` HTTP assertions (`assertOk`, `assertStatus(403)` etc.) | `IntegrationTestCase` |
| Console Commands | `artisan()` helper or `command->handle()` with mocked services | `IntegrationTestCase` |

---

## Coverage Verification Command

```bash
# Sequential coverage collection (avoids parallel OOM)
XDEBUG_MODE=coverage php -d memory_limit=3G vendor/bin/pest \
  --coverage-xml=storage/infection/coverage \
  --log-junit=storage/infection/junit.xml \
  2>&1 | tail -10
```

Then parse the result:
```bash
python3 scripts/testing/parse-coverage-index.py
```

---

## Phase Documents

1. [`phase-1-emailmigration.md`](phase-1-emailmigration.md) — 64.3% → ~69%
2. [`phase-2-clientportal-deploymentmanager-app-layer.md`](phase-2-clientportal-deploymentmanager-app-layer.md) — ~69% → ~72%
3. [`phase-3-action1-google-assetmgmt-app-http.md`](phase-3-action1-google-assetmgmt-app-http.md) — ~72% → ~77%
4. [`phase-4-crm-pib-contractmanager-stretch.md`](phase-4-crm-pib-contractmanager-stretch.md) — ~77% → **≥ 80%** ✅
