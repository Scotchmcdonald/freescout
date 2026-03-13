<?php

declare(strict_types=1);

namespace App\Traits;

trait ExtensibleModel
{
    /** @var array<int, string> */
    protected static array $externalFillables = [];
    /** @var array<string, string> */
    protected static array $externalCasts = [];

    /**
     * Add fillable attributes from external sources (e.g. Modules)
     *
     * @param  array<int, string>  $fillables
     */
    public static function addGlobalFillables(array $fillables): void
    {
        static::$externalFillables = array_values(array_unique(array_merge(static::$externalFillables, $fillables)));
    }

    /**
     * Add casts from external sources
     *
     * @param  array<string, string>  $casts
     */
    public static function addGlobalCasts(array $casts): void
    {
        static::$externalCasts = array_merge(static::$externalCasts, $casts);
    }

    /**
     * Initialize the trait.
     * Eloquent calls this automatically if the trait name matches initialize{TraitName}
     */
    public function initializeExtensibleModel(): void
    {
        if (! empty(static::$externalFillables)) {
            $this->mergeFillable(static::$externalFillables);
        }

        if (! empty(static::$externalCasts)) {
            $this->mergeCasts(static::$externalCasts);
        }
    }
}
