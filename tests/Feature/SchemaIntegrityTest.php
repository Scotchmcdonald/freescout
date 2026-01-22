<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class SchemaIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * list of folders to scan for models
     */
    protected $modelPaths = [
        'app/Models',
        'Modules' // We will traverse deeper here
    ];

    /**
     * Models known to be problematic or special cases to skip
     */
    protected $ignoreModels = [
        // 'App\Models\SomeAbstractModel',
    ];

    /**
     * Test that every Eloquent model has a corresponding database table.
     */
    public function test_all_models_have_tables()
    {
        $models = $this->getModels();
        $failures = [];

        foreach ($models as $class) {
            $model = new $class();
            $table = $model->getTable();

            if (!Schema::hasTable($table)) {
                $failures[] = "Missing table '{$table}' for model '{$class}'";
            }
        }

        $this->assertEmpty($failures, implode("\n", $failures));
    }

    /**
     * Test that all fillable attributes in models actually exist in the database.
     */
    public function test_all_fillable_columns_exist()
    {
        $models = $this->getModels();
        $failures = [];

        foreach ($models as $class) {
            $model = new $class();
            $table = $model->getTable();
            
            if (!Schema::hasTable($table)) {
                continue; // Already caught by previous test
            }

            $fillables = $model->getFillable();
            $columns = Schema::getColumnListing($table);

            foreach ($fillables as $fillable) {
                if (!in_array($fillable, $columns)) {
                    $failures[] = "Column '{$fillable}' defined in \$fillable missing in table '{$table}' for model '{$class}'";
                }
            }
        }

        $this->assertEmpty($failures, implode("\n", $failures));
    }

    /**
     * Helper to discover all Model classes.
     */
    protected function getModels()
    {
        $models = [];
        $basePath = base_path();

        // 1. Scan app/Models
        $models = array_merge($models, $this->scanDirectory($basePath . '/app/Models', 'App\\Models'));

        // 2. Scan Modules
        $modulesPath = $basePath . '/Modules';
        if (File::exists($modulesPath)) {
            $modules = File::directories($modulesPath);
            foreach ($modules as $modulePath) {
                $moduleName = basename($modulePath);
                
                // Check Models dir
                if (File::exists($modulePath . '/Models')) {
                    $models = array_merge($models, $this->scanDirectory($modulePath . '/Models', "Modules\\{$moduleName}\\Models"));
                }
                
                // Check Entities dir
                if (File::exists($modulePath . '/Entities')) {
                     $models = array_merge($models, $this->scanDirectory($modulePath . '/Entities', "Modules\\{$moduleName}\\Entities"));
                }
            }
        }

        return $models;
    }

    protected function scanDirectory($path, $namespace)
    {
        $classes = [];
        $files = File::allFiles($path);

        foreach ($files as $file) {
            $className = $namespace . '\\' . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

            if (class_exists($className)) {
                $reflection = new ReflectionClass($className);
                if ($reflection->isSubclassOf(Model::class) && !$reflection->isAbstract()) {
                     $classes[] = $className;
                }
            }
        }

        return $classes;
    }
}
