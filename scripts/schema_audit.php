<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$results = [];

// 1. Core App Models
$corePath = base_path('app/Models');
$results['Core App'] = analyze_path($corePath, 'App\\Models\\');

// 2. Modules
$modules = Module::all();
foreach ($modules as $module) {
    $name = $module->getName();
    $path = module_path($name, 'Models');
    if (File::exists($path)) {
        $results["Module: $name"] = analyze_path($path, "Modules\\$name\\Models\\");
    }
    
    // Also list migrations
    $migrationPath = module_path($name, 'Database/Migrations');
    if (File::exists($migrationPath)) {
        $results["Module: $name"]['migrations'] = array_map(function($file) {
            return $file->getFilename();
        }, File::files($migrationPath));
    }
}

// core migrations
$coreMigrationPath = base_path('database/migrations');
$results['Core App']['migrations'] = array_map(function($file) {
    return $file->getFilename();
}, File::files($coreMigrationPath));

// Output Report
echo "# Schema vs Model Audit Report\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($results as $group => $data) {
    echo "## $group\n\n";
    
    if (isset($data['migrations'])) {
        echo "### Existing Migrations (" . count($data['migrations']) . ")\n";
        foreach ($data['migrations'] as $mig) {
            echo "- $mig\n";
        }
        echo "\n";
    }

    if (isset($data['models'])) {
        foreach ($data['models'] as $modelName => $info) {
            echo "### Model: $modelName\n";
            echo "**Table:** `{$info['table']}`\n\n";
            
            if ($info['table_exists']) {
                echo "| Field | In Model (Fillable) | In DB (Column) | Type |\n";
                echo "|-------|---------------------|----------------|------|\n";
                
                $allFields = array_unique(array_merge($info['fillable'], $info['columns']));
                sort($allFields);
                
                foreach ($allFields as $field) {
                    $inModel = in_array($field, $info['fillable']) ? '✅' : '❌';
                    $inDB = in_array($field, $info['columns']) ? '✅' : '❌';
                    $type = $info['column_types'][$field] ?? 'N/A';
                    
                    // Filter out timestamps/id from "Not in Model" noise usually
                    if (!$inModel && in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                        $inModel = '(Auto)';
                    }
                    
                    echo "| $field | $inModel | $inDB | $type |\n";
                }
            } else {
                echo "⚠️ **TABLE NOT FOUND IN DATABASE**\n";
            }
            echo "\n";
        }
    }
}

function analyze_path($path, $namespacePrefix) {
    $data = ['models' => []];
    $files = File::files($path);
    
    foreach ($files as $file) {
        $className = $namespacePrefix . $file->getFilenameWithoutExtension();
        
        try {
            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                if ($reflection->isAbstract() || !$reflection->isSubclassOf(Model::class)) {
                    continue;
                }
                
                $model = new $className;
                $table = $model->getTable();
                
                $info = [
                    'table' => $table,
                    'table_exists' => Schema::hasTable($table),
                    'fillable' => $model->getFillable(),
                    'columns' => [],
                    'column_types' => []
                ];
                
                if ($info['table_exists']) {
                    $verifyColumns = Schema::getColumnListing($table);
                    $info['columns'] = $verifyColumns;
                    foreach ($verifyColumns as $col) {
                        $info['column_types'][$col] = Schema::getColumnType($table, $col);
                    }
                }
                
                $data['models'][$className] = $info;
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }
    return $data;
}
