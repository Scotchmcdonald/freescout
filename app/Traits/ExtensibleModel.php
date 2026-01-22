<?php

namespace App\Traits;

trait ExtensibleModel
{
    protected static $externalFillables = [];
    protected static $externalCasts = [];

    /**
     * Add fillable attributes from external sources (e.g. Modules)
     * 
     * @param array $fillables
     */
    public static function addGlobalFillables(array $fillables)
    {
        static::$externalFillables = array_values(array_unique(array_merge(static::$externalFillables, $fillables)));
    }

    /**
     * Add casts from external sources
     * 
     * @param array $casts
     */
    public static function addGlobalCasts(array $casts)
    {
        static::$externalCasts = array_merge(static::$externalCasts, $casts);
    }

    /**
     * Initialize the trait.
     * Eloquent calls this automatically if the trait name matches initialize{TraitName}
     */
    public function initializeExtensibleModel()
    {
        if (!empty(static::$externalFillables)) {
            $this->mergeFillable(static::$externalFillables);
        }
        
        if (!empty(static::$externalCasts)) {
            $this->mergeCasts(static::$externalCasts);
        }
    }
}
