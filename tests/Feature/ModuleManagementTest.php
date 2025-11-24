<?php

namespace Tests\Feature;

use App\Services\ModuleSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;
use App\Models\User;

class ModuleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]); // Admin
    }

    public function test_index_displays_available_modules()
    {
        // Mock ModuleSource
        $mockSource = Mockery::mock(ModuleSource::class);
        $mockSource->shouldReceive('getModules')->andReturn([
            [
                'name' => 'Test Module',
                'alias' => 'testmodule',
                'description' => 'A test module',
                'version' => '1.0.0',
                'price' => 'Free',
            ]
        ]);

        $this->app->instance(ModuleSource::class, $mockSource);

        $response = $this->actingAs($this->admin)->get(route('modules'));

        $response->assertStatus(200);
        $response->assertSee('Test Module');
        $response->assertSee('A test module');
    }

    public function test_install_downloads_and_installs_module()
    {
        // Mock ModuleSource
        $mockSource = Mockery::mock(ModuleSource::class);
        $mockSource->shouldReceive('getModule')->with('testmodule')->andReturn([
            'name' => 'Test Module',
            'alias' => 'testmodule',
            'download_url' => 'https://example.com/testmodule.zip',
        ]);
        $this->app->instance(ModuleSource::class, $mockSource);

        // Mock Http for download
        Http::fake([
            'https://example.com/testmodule.zip' => Http::response($this->createZipContent(), 200),
        ]);

        // Ensure Modules directory exists
        if (!File::isDirectory(base_path('Modules'))) {
            File::makeDirectory(base_path('Modules'));
        }
        
        // Clean up before test
        if (File::isDirectory(base_path('Modules/TestModule'))) {
            File::deleteDirectory(base_path('Modules/TestModule'));
        }

        $response = $this->actingAs($this->admin)->post(route('modules.install'), [
            'alias' => 'testmodule',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify module directory exists (zip extraction worked)
        // Note: The zip content created below extracts to 'TestModule' folder
        $this->assertTrue(File::isDirectory(base_path('Modules/TestModule')));

        // Clean up
        File::deleteDirectory(base_path('Modules/TestModule'));
    }

    protected function createZipContent()
    {
        $zipFile = tempnam(sys_get_temp_dir(), 'zip');
        $zip = new \ZipArchive();
        $zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addEmptyDir('TestModule');
        $zip->addFromString('TestModule/module.json', '{"name": "TestModule", "alias": "testmodule", "description": "Test", "keywords": [], "priority": 0, "providers": [], "aliases": {}, "files": [], "requires": []}');
        $zip->close();
        
        $content = file_get_contents($zipFile);
        unlink($zipFile);
        
        return $content;
    }
}
