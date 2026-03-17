<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

function getModels()
{
    $models = [];
    $basePath = base_path();

    // Helper to scan directory
    $scanDirectory = function ($dir, $namespacePrefix) {
        $found = [];
        if (! File::exists($dir)) {
            return [];
        }
        $files = File::allFiles($dir);
        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $class = $namespacePrefix.'\\'.str_replace(['/', '.php'], ['\\', ''], $relativePath);
            if (class_exists($class) && is_subclass_of($class, \Illuminate\Database\Eloquent\Model::class)) {
                $found[] = $class;
            }
        }

        return $found;
    };

    // 1. Scan app/Models
    $models = array_merge($models, $scanDirectory($basePath.'/app/Models', 'App\\Models'));

    // 2. Scan Modules
    $modulesPath = $basePath.'/Modules';
    if (File::exists($modulesPath)) {
        $modules = File::directories($modulesPath);
        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            // Check Models dir
            if (File::exists($modulePath.'/Models')) {
                $models = array_merge($models, $scanDirectory($modulePath.'/Models', "Modules\\{$moduleName}\\Models"));
            }
        }
    }

    return $models;
}

test('all archived tables exist', function () {
    $requiredTables = [
        'users',
        'customers',
        'conversations',
        'threads',
        'mailboxes',
        'folders',
        'attachments',
        'options',
        'activity_log',
        'jobs',
        'failed_jobs',
        'password_reset_tokens',
        'sessions',
    ];

    foreach ($requiredTables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Required table '{$table}' from archived app does not exist in modernized schema");
    }
});

test('all models have tables', function () {
    $models = getModels();
    $failures = [];

    foreach ($models as $class) {
        // Skip abstract or base classes if any were picked up (is_subclass checks instances, but we need to check if abstract)
        $ref = new ReflectionClass($class);
        if ($ref->isAbstract()) {
            continue;
        }

        $model = new $class;
        $table = $model->getTable();

        if (! Schema::hasTable($table)) {
            $failures[] = "Missing table '{$table}' for model '{$class}'";
        }
    }

    expect($failures)->toBeEmpty(implode("\n", $failures));
});
