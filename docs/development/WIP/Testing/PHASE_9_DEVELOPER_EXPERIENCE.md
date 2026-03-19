# Phase 9: Developer Experience

## Objective

Make correct testing behavior the easiest default for contributors.

## Current Baseline

- testing standards doc exists and is substantial
- referenced maintenance cadence doc is missing
- CI uses broad phpunit workflows, but lane-specific testing is not explicit
- explicit parallel lane settings are not present in current workflows
- coverage and mutation visibility are missing

## Exit Criteria

- docs are complete and linked
- CI lanes are explicit and understandable
- the PR gate lane is fast and clearly reported
- developers can discover the right test layer and command without asking for help

## Implementation Plan

1. Fill missing docs.
2. Split workflows into understandable lanes.
3. Document the default commands and report locations.
4. Publish artifacts that make failures actionable.

## Autonomous Execution Guidance

Autonomy level: high.

The agent should autonomously:
- add or repair docs
- restructure workflow YAML for clarity
- expose report locations and lane purposes

Pause only if:
- the team must choose between conflicting CI cost and latency goals

## Safe Command Patterns

```bash
ls .github/workflows
grep -RIn "php artisan test\|parallel\|coverage\|infection" .github/workflows --include='*.yml' --include='*.yaml'
php artisan about
```

## Effective LLM Prompt

```text
You are executing Phase 9 of the testing roadmap: Developer Experience.

Work autonomously where possible. Improve the testing docs, workflow clarity, report visibility, and default command guidance so a contributor can run the correct lane and understand failures quickly. Favor simple, explicit CI lanes over opaque all-in-one jobs.

Continue until the testing workflow is easier to discover, run, and debug.
```
