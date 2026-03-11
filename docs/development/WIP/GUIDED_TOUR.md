# Guided Tour Feature — Implementation Plan

> **Last Updated:** 2026-03-11  
> **Status:** Not started. Zero code exists.  
> **Library:** Driver.js (already in `package.json`? verify before adding)

---

## Database Schema

- [ ] Migration: `user_tour_progress` table — `user_id`, `tour_id` (string), `completed_steps` (JSON), `completed_at` (nullable), timestamps.
- [ ] Migration: `tour_analytics` table — `user_id`, `tour_id`, `step_id`, `action` (started/skipped/completed), `created_at`.

---

## Config

- [ ] Create `config/tours.php`: define tours by ID with step definitions, depth level (`high_level` | `detailed` | `whats_new`), and trigger conditions (e.g. `first_login`, `feature_flag`).

---

## Core Logic

- [ ] **Depth-Based Logic**: serve different step sets based on user preference or role (High Level / Detailed / What's New).
- [ ] **Multi-Page Persistence**: store in-progress tour state in `user_tour_progress`; resume across page navigations via session or API check on page load.
- [ ] **Intelligent State Detection**: auto-skip steps where the user has already taken the action (check via `config/tours.php` trigger conditions + user state query).

---

## Frontend

- [ ] Blade partial `resources/views/components/guided-tour.blade.php`: conditionally injects Driver.js config JSON if an active tour exists for the current user + route.
- [ ] JS: `resources/js/guided-tour.js` — initialize Driver.js from injected config, POST step completions to `/api/tour-progress`, handle Escape exit.
- [ ] Accessibility: keyboard navigation (Tab/Enter to advance, Escape to exit); ensure Driver.js overlay doesn't trap screen readers.
- [ ] Deferred: Mobile/Responsive — Bottom Sheet approach for small screens (do after desktop is stable).

---

## API

- [ ] `GET  /api/tour/{tour_id}` — returns step config for a tour if user has not completed it.
- [ ] `POST /api/tour-progress` — records a step completion or tour dismissal.
