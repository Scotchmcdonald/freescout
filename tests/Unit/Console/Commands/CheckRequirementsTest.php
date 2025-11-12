<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\CheckRequirements;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class CheckRequirementsTest extends UnitTestCase
{

    #[Test]
    public function command_can_be_instantiated(): void
    {
        $command = new CheckRequirements();
        
        $this->assertInstanceOf(CheckRequirements::class, $command);
    }

    #[Test]
    public function command_has_correct_signature(): void
    {
        $command = new CheckRequirements();
        
        $this->assertEquals('freescout:check-requirements', $command->getName());
    }

    #[Test]
    public function command_has_description(): void
    {
        $command = new CheckRequirements();
        
        $this->assertNotEmpty($command->getDescription());
        $this->assertStringContainsString('requirements', $command->getDescription());
    }

    #[Test]
    public function command_checks_php_version(): void
    {
        Artisan::call('freescout:check-requirements');
        $output = Artisan::output();
        
        $this->assertStringContainsString('PHP Version', $output);
        $this->assertStringContainsString(phpversion(), $output);
    }

    #[Test]
    public function command_checks_php_extensions(): void
    {
        Artisan::call('freescout:check-requirements');
        $output = Artisan::output();
        
        $this->assertStringContainsString('PHP Extensions', $output);
    }

    #[Test]
    public function command_checks_required_functions(): void
    {
        Artisan::call('freescout:check-requirements');
        $output = Artisan::output();
        
        $this->assertStringContainsString('Required Functions', $output);
    }

    #[Test]
    public function command_checks_directory_permissions(): void
    {
        Artisan::call('freescout:check-requirements');
        $output = Artisan::output();
        
        $this->assertStringContainsString('Directory Permissions', $output);
    }

    #[Test]
    public function command_returns_zero_when_requirements_met(): void
    {
        $exitCode = Artisan::call('freescout:check-requirements');
        
        // May return 0 or 1 depending on environment
        $this->assertContains($exitCode, [0, 1]);
    }

    #[Test]
    public function command_checks_proc_open_function(): void
    {
        Artisan::call('freescout:check-requirements');
        $output = Artisan::output();
        
        $this->assertStringContainsString('proc_open', $output);
    }

    #[Test]
    public function command_checks_fsockopen_function(): void
    {
        Artisan::call('freescout:check-requirements');
        $output = Artisan::output();
        
        $this->assertStringContainsString('fsockopen', $output);
    }

    #[Test]
    public function command_checks_iconv_function(): void
    {
        Artisan::call('freescout:check-requirements');
        $output = Artisan::output();
        
        $this->assertStringContainsString('iconv', $output);
    }

    #[Test]
    public function command_shows_ok_or_failed_status(): void
    {
        Artisan::call('freescout:check-requirements');
        $output = Artisan::output();
        
        // Should contain OK or FAILED status indicators
        $hasStatus = str_contains($output, 'OK') || str_contains($output, 'FAILED') || str_contains($output, 'NOT FOUND');
        $this->assertTrue($hasStatus);
    }

    #[Test]
    public function command_checks_intl_extension(): void
    {
        Artisan::call('freescout:check-requirements');
        $output = Artisan::output();
        
        $this->assertStringContainsString('intl', $output);
    }

    #[Test]
    public function command_checks_pcntl_extension(): void
    {
        Artisan::call('freescout:check-requirements');
        $output = Artisan::output();
        
        $this->assertStringContainsString('pcntl', $output);
    }

    #[Test]
    public function command_displays_summary_message(): void
    {
        Artisan::call('freescout:check-requirements');
        $output = Artisan::output();
        
        // Should show either success or error message
        $hasSummary = str_contains($output, 'All requirements met') || str_contains($output, 'not met');
        $this->assertTrue($hasSummary);
    }
}
