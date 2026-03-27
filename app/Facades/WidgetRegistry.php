<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class WidgetRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\Ui\WidgetRegistryService::class;
    }
}
