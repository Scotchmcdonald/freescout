<?php

declare(strict_types=1);

namespace Modules\MiddleMan\Contracts;

/**
 * Opt-in interface for Eloquent models that should be queryable via the
 * MiddleMan Marshal tab's async model-search endpoint.
 *
 * The endpoint (`GET /middleman/marshal/search-model`) is gated by the
 * `can:view_middleman` middleware, but internal misuse (or compromise of an
 * operator account) could expose sensitive model data if any Eloquent model
 * is accepted.
 *
 * A model becomes searchable when it EITHER:
 *   (a) Implements this interface, OR
 *   (b) Its FQCN is listed in the `middleman.searchable_models` config array
 *       (supports environment-driven allowlisting without code changes).
 *
 * Usage (recommended):
 * ─────────────────────────────────────────────────────────────────────────
 *   use Modules\MiddleMan\Contracts\MiddleManSearchable;
 *
 *   class User extends Authenticatable implements MiddleManSearchable { ... }
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Config-only approach (no code change to the model):
 * ─────────────────────────────────────────────────────────────────────────
 *   # .env
 *   MIDDLEMAN_SEARCHABLE_MODELS=App\Models\User,App\Models\Company
 * ─────────────────────────────────────────────────────────────────────────
 */
interface MiddleManSearchable
{
    // Marker interface — no methods required.
    // Implement it on any Eloquent model to make it searchable in Marshal.
}
