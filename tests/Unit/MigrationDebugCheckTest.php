<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationDebugCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_companies_table_has_crm_columns()
    {
        $this->assertTrue(Schema::hasTable('companies'), 'Companies table missing');

        $columns = Schema::getColumnListing('companies');
        dump('Columns in companies table:', $columns);

        $this->assertTrue(Schema::hasColumn('companies', 'settings'), 'settings column missing');

        // Also check if CrmServiceProvider is loaded
        $loaded = array_keys($this->app->getLoadedProviders());
        // dump('Loaded providers:', $loaded);
        $this->assertContains('Modules\Crm\Providers\CrmServiceProvider', $loaded);
    }
}
