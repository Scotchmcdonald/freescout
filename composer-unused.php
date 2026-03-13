<?php

// composer-unused.php
use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

return static function (Configuration $config): Configuration {
    return $config
        // 1. Tell the scanner to include your Modules directory
        // ->addAdditionalScanDirectory(__DIR__ . '/Modules')

        // 2. Whitelist packages that are triggered by config/env (False Positives)
        ->addNamedFilter(NamedFilter::fromString('laravel/reverb'))
        ->addNamedFilter(NamedFilter::fromString('doctrine/dbal'))
        ->addNamedFilter(NamedFilter::fromString('enshrined/svg-sanitize'))

        // 3. Whitelist packages used only in Blade (Static analysis can't see these)
        ->addNamedFilter(NamedFilter::fromString('owenvoke/blade-entypo'));
};
