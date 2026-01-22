<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class WidgetRegistry extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\WidgetRegistry::class;
    }
}
