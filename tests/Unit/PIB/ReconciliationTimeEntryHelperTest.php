<?php

declare(strict_types=1);

namespace Tests\Unit\PIB;

use Modules\PIB\Models\ReconciliationRun;
use Modules\PIB\Models\TimeEntry;
use Tests\PureUnitTestCase;

final class TestReconciliationRun extends ReconciliationRun
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

final class TestTimeEntry extends TimeEntry
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

class ReconciliationTimeEntryHelperTest extends PureUnitTestCase
{
    private function reconciliationRun(array $attrs = []): TestReconciliationRun
    {
        $model = new TestReconciliationRun;
        foreach ($attrs as $key => $value) {
            $model->{$key} = $value;
        }

        return $model;
    }

    private function timeEntry(array $attrs = []): TestTimeEntry
    {
        $model = new TestTimeEntry;
        foreach ($attrs as $key => $value) {
            $model->{$key} = $value;
        }

        return $model;
    }

    public function test_is_complete_true_for_completed_failed_or_partial_only(): void
    {
        $this->assertTrue($this->reconciliationRun(['status' => 'completed'])->isComplete());
        $this->assertTrue($this->reconciliationRun(['status' => 'failed'])->isComplete());
        $this->assertTrue($this->reconciliationRun(['status' => 'partial'])->isComplete());
        $this->assertFalse($this->reconciliationRun(['status' => 'running'])->isComplete());
    }

    public function test_is_running_true_only_for_running_status(): void
    {
        $this->assertTrue($this->reconciliationRun(['status' => 'running'])->isRunning());
        $this->assertFalse($this->reconciliationRun(['status' => 'completed'])->isRunning());
    }

    public function test_is_successful_requires_completed_status_and_zero_critical_issues(): void
    {
        $completedClean = $this->reconciliationRun(['status' => 'completed', 'critical_issues' => 0]);
        $completedIssues = $this->reconciliationRun(['status' => 'completed', 'critical_issues' => 2]);
        $failedClean = $this->reconciliationRun(['status' => 'failed', 'critical_issues' => 0]);

        $this->assertTrue($completedClean->isSuccessful());
        $this->assertFalse($completedIssues->isSuccessful());
        $this->assertFalse($failedClean->isSuccessful());
    }

    public function test_calculate_success_rate_handles_zero_checked_items(): void
    {
        $rate = $this->reconciliationRun(['items_checked' => 0, 'total_discrepancies' => 0])->calculateSuccessRate();

        $this->assertSame(0.0, $rate);
    }

    public function test_calculate_success_rate_returns_rounded_percentage(): void
    {
        $rate = $this->reconciliationRun(['items_checked' => 3, 'total_discrepancies' => 1])->calculateSuccessRate();

        $this->assertSame(66.67, $rate);
    }

    public function test_get_status_info_mappings_and_dynamic_completed_color_message(): void
    {
        $running = $this->reconciliationRun(['status' => 'running'])->getStatusInfo();
        $completedSuccess = $this->reconciliationRun(['status' => 'completed', 'critical_issues' => 0])->getStatusInfo();
        $completedWarning = $this->reconciliationRun(['status' => 'completed', 'critical_issues' => 1])->getStatusInfo();
        $failed = $this->reconciliationRun(['status' => 'failed'])->getStatusInfo();
        $partial = $this->reconciliationRun(['status' => 'partial'])->getStatusInfo();
        $unknown = $this->reconciliationRun(['status' => 'mystery'])->getStatusInfo();

        $this->assertSame('primary', $running['color']);

        $this->assertSame('success', $completedSuccess['color']);
        $this->assertSame('Completed successfully', $completedSuccess['message']);

        $this->assertSame('warning', $completedWarning['color']);
        $this->assertSame('Completed with critical issues', $completedWarning['message']);

        $this->assertSame('danger', $failed['color']);
        $this->assertSame('warning', $partial['color']);

        $this->assertSame('unknown', $unknown['status']);
        $this->assertSame('gray', $unknown['color']);
    }

    public function test_get_duration_attribute_formats_seconds_and_minutes(): void
    {
        $none = $this->reconciliationRun(['duration_seconds' => null]);
        $secondsOnly = $this->reconciliationRun(['duration_seconds' => 45]);
        $minutesSeconds = $this->reconciliationRun(['duration_seconds' => 125]);

        $this->assertNull($none->duration);
        $this->assertSame('45s', $secondsOnly->duration);
        $this->assertSame('2m 5s', $minutesSeconds->duration);
    }

    public function test_time_entry_duration_hours_and_formatted_duration(): void
    {
        $ninety = $this->timeEntry(['duration_minutes' => 90]);
        $sixty = $this->timeEntry(['duration_minutes' => 60]);
        $fortyFive = $this->timeEntry(['duration_minutes' => 45]);

        $this->assertSame(1.5, $ninety->duration_hours);

        $this->assertSame('1h 30m', $ninety->formatted_duration);
        $this->assertSame('1h', $sixty->formatted_duration);
        $this->assertSame('45m', $fortyFive->formatted_duration);
    }

    public function test_time_entry_total_amount_returns_null_without_rate_and_computed_value_with_rate(): void
    {
        $noRate = $this->timeEntry(['duration_minutes' => 120, 'billing_rate' => null]);
        $withRate = $this->timeEntry(['duration_minutes' => 135, 'billing_rate' => 80]);

        $this->assertNull($noRate->total_amount);
        $this->assertSame(180.0, $withRate->total_amount);
    }

    public function test_time_entry_is_invoiced_checks_service_usage_id_presence(): void
    {
        $notInvoiced = $this->timeEntry(['service_usage_id' => null]);
        $invoiced = $this->timeEntry(['service_usage_id' => 42]);

        $this->assertFalse($notInvoiced->isInvoiced());
        $this->assertTrue($invoiced->isInvoiced());
    }
}
