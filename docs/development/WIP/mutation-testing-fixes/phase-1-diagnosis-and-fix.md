# Mutation Testing CI Pipeline Fixes

## Status: Complete

## Issues Diagnosed

### 1. TestFileNameNotFoundException (Root Cause)
Coverage XML and JUnit XML were generated from **different pest runs**, causing class-name mismatches:
- `check-line-coverage.sh` produced coverage XML (with MiddleMan test references) but NO JUnit
- `check-mutation-tier2.sh` had its own coverage prep that generated JUnit from a different session
- Infection crashed with `TestFileNameNotFoundException: P\Tests\Feature\MiddleMan\MiddleManFeatureTest`

### 2. Triple Redundant Mutation Testing
Mutation testing ran 3 times per CI pipeline:
1. Via `check-architecture-compliance.sh` (calling `check-mutation-tier2.sh`)
2. Via `check-mutation-tier2.sh` standalone (glob pickup)
3. Via `test-with-coverage-and-mutation.sh` (calling `check-mutation-tier2.sh`)

### 3. Missing Test Suites in Infection Config
`config/infection/phpunit.xml` only included Unit, PIB, ContractManager, Payment,
SoftwareSubscriptions, CaseManager suites. Missing: Feature, Integration, Action1,
AssetManagement, Crm — any test covering the mutation scope.

### 4. No Per-Mutant Timeout
With `--skip-initial-tests`, Infection had no baseline for adaptive timeouts.
Hanging mutants blocked threads indefinitely.

### 5. Xdebug Loaded During Mutation
`XDEBUG_MODE=off` kept xdebug loaded (just inactive). Extension loading overhead
applied to every mutant subprocess (~5-10% slowdown).

### 6. Invalid XML Comment
`config/infection/phpunit.xml` comment contained `--only-covering-test-cases`
which has `--` inside XML comments (prohibited by XML spec).

## Fixes Applied

| File | Change |
|------|--------|
| `scripts/ci/check-line-coverage.sh` | Added `--log-junit` to produce JUnit alongside coverage in ONE pest run. Added JUnit normalization step. Fixed `bc` dependency → `awk`. |
| `scripts/ci/check-mutation-tier2.sh` | Removed coverage prep (requires pre-existing artifacts). Added strict prerequisite checks. Added `--only-covering-test-cases`. Xdebug exclusion via `PHP_INI_SCAN_DIR`. Increased timeout 45→90 min, threads 6→10. |
| `scripts/ci/check-architecture-compliance.sh` | Removed `check-mutation-tier2.sh` call (mutation is its own CI step). |
| `scripts/ci/test-with-coverage-and-mutation.reference.sh` | Renamed from `.sh` to `.reference.sh` to prevent ci.sh glob pickup. |
| `config/infection/phpunit.xml` | Added Feature, Integration, Action1, AssetManagement, Crm test suites. Updated source directories. Fixed XML comment. Reduced per-process memory 4G→512M. |
| `infection-extended.json5` | Added `"timeout": 30` for per-mutant timeout. |
| `infection.json5` | Added `"timeout": 30` for consistency. |
| `scripts/ci.sh` | Updated default INFECTION_THREADS 6→10. |
