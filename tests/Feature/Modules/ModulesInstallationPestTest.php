<?php

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->originalModulesPath = config('modules.paths.modules');
    $this->originalDiscoveryPaths = config('modules.discovery.paths');
    $this->originalStatusesFile = config('modules.activators.file.statuses-file');

    $token = preg_replace('/[^a-z0-9]/', '', strtolower((string) (env('TEST_TOKEN') ?? getmypid())));
    $this->testModulesPath = storage_path('framework/testing/modules-installation/worker_'.$token);
    $this->testStatusesFile = $this->testModulesPath.'/modules_statuses.json';

    if (! File::isDirectory($this->testModulesPath)) {
        File::makeDirectory($this->testModulesPath, 0755, true);
    }
    if (! File::exists($this->testStatusesFile)) {
        File::put($this->testStatusesFile, '{}');
    }

    config([
        'modules.paths.modules' => $this->testModulesPath,
        'modules.discovery.paths' => [$this->testModulesPath],
        'modules.activators.file.statuses-file' => $this->testStatusesFile,
    ]);

    $uniqueSuffix = substr(str_replace('.', '', uniqid('', true)), -6);
    $this->moduleName = 'TestModule'.$token.$uniqueSuffix;
    $this->moduleAlias = strtolower($this->moduleName);
    $this->modulePath = $this->testModulesPath.'/'.$this->moduleName;
});

afterEach(function () {
    config([
        'modules.paths.modules' => $this->originalModulesPath,
        'modules.discovery.paths' => $this->originalDiscoveryPaths,
        'modules.activators.file.statuses-file' => $this->originalStatusesFile,
    ]);

    if (File::isDirectory($this->testModulesPath)) {
        File::deleteDirectory($this->testModulesPath);
    }
});

test('install downloads and installs module', function () {
    // Mock WpApi response and download
    Http::fake([
        '*/freescout/v1/modules*' => Http::response([
            [
                'name' => 'Test Module',
                'alias' => $this->moduleAlias,
                'download_url' => 'https://example.com/'.$this->moduleAlias.'.zip',
            ],
        ], 200),
        'https://example.com/'.$this->moduleAlias.'.zip' => Http::response(createZipContent($this->moduleName, $this->moduleAlias), 200),
    ]);

    // Ensure isolated modules directory exists
    if (! File::isDirectory($this->testModulesPath)) {
        File::makeDirectory($this->testModulesPath, 0755, true);
    }

    $response = $this->actingAs($this->admin)->post(route('modules.install'), [
        'alias' => $this->moduleAlias,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Verify module directory exists (zip extraction worked)
    expect(File::isDirectory($this->modulePath))->toBeTrue();
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

function createZipContent(string $moduleName, string $moduleAlias)
{
    $zipFile = tempnam(sys_get_temp_dir(), 'zip');
    $zip = new \ZipArchive;
    $zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
    $zip->addEmptyDir($moduleName);
    $zip->addFromString(
        $moduleName.'/module.json',
        json_encode([
            'name' => $moduleName,
            'alias' => $moduleAlias,
            'description' => 'Test',
            'keywords' => [],
            'priority' => 0,
            'providers' => [],
            'aliases' => (object) [],
            'files' => [],
            'requires' => [],
        ], JSON_THROW_ON_ERROR)
    );
    $zip->close();

    $content = file_get_contents($zipFile);
    unlink($zipFile);

    return $content;
}
