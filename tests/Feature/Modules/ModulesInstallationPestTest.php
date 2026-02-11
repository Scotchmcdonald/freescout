<?php

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;


beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
});

test('install downloads and installs module', function () {
    // Mock WpApi response and download
    Http::fake([
        '*/freescout/v1/modules*' => Http::response([
            [
                'name' => 'Test Module',
                'alias' => 'testmodule',
                'download_url' => 'https://example.com/testmodule.zip',
            ]
        ], 200),
        'https://example.com/testmodule.zip' => Http::response(createZipContent(), 200),
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
    expect(File::isDirectory(base_path('Modules/TestModule')))->toBeTrue();

    // Clean up
    File::deleteDirectory(base_path('Modules/TestModule'));
});

test('install module requires alias', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('modules.install'), []);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('install module returns error for unknown alias', function () {
    // Mock the API to return empty list or specific response if needed
    // Assuming the controller calls the API to verify alias or look up download URL
    Http::fake([
        '*/freescout/v1/modules*' => Http::response([], 200),
    ]);
    
    $response = $this->actingAs($this->admin)
        ->post(route('modules.install'), [
            'alias' => 'non-existent-module',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

function createZipContent()
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
