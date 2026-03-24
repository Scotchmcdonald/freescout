<?php

declare(strict_types=1);

namespace Tests\Unit\CaseManager;

use Illuminate\Support\Carbon;
use Modules\CaseManager\Models\Diagnostic;
use Modules\CaseManager\Models\QuickWin;
use Tests\PureUnitTestCase;

final class TestDiagnostic extends Diagnostic
{
    protected function casts(): array
    {
        return [];
    }
}

final class TestQuickWin extends QuickWin
{
    protected function casts(): array
    {
        return [];
    }
}

class DiagnosticQuickWinHelperTest extends PureUnitTestCase
{
    private function diagnostic(string $status, ?Carbon $startedAt = null, ?Carbon $completedAt = null): TestDiagnostic
    {
        $diagnostic = new TestDiagnostic;
        $diagnostic->status = $status;
        $diagnostic->started_at = $startedAt;
        $diagnostic->completed_at = $completedAt;

        return $diagnostic;
    }

    public function test_is_complete_returns_true_for_completed_status(): void
    {
        $this->assertTrue($this->diagnostic('completed')->isComplete());
    }

    public function test_is_complete_returns_true_for_failed_status(): void
    {
        $this->assertTrue($this->diagnostic('failed')->isComplete());
    }

    public function test_is_complete_returns_true_for_timed_out_status(): void
    {
        $this->assertTrue($this->diagnostic('timed_out')->isComplete());
    }

    public function test_is_complete_returns_false_for_running_status(): void
    {
        $this->assertFalse($this->diagnostic('running')->isComplete());
    }

    public function test_is_successful_returns_true_only_for_completed_status(): void
    {
        $this->assertTrue($this->diagnostic('completed')->isSuccessful());
        $this->assertFalse($this->diagnostic('failed')->isSuccessful());
    }

    public function test_duration_returns_null_when_started_at_is_missing(): void
    {
        $diagnostic = $this->diagnostic('completed', null, Carbon::parse('2026-03-24 10:00:00'));

        $this->assertNull($diagnostic->duration());
    }

    public function test_duration_returns_null_when_completed_at_is_missing(): void
    {
        $diagnostic = $this->diagnostic('completed', Carbon::parse('2026-03-24 10:00:00'), null);

        $this->assertNull($diagnostic->duration());
    }

    public function test_duration_returns_diff_in_seconds_when_both_timestamps_exist(): void
    {
        $diagnostic = $this->diagnostic(
            'completed',
            Carbon::parse('2026-03-24 10:00:00'),
            Carbon::parse('2026-03-24 10:01:40')
        );

        $this->assertSame(100, $diagnostic->duration());
    }

    public function test_quick_win_is_pending_returns_true_for_suggested_status(): void
    {
        $quickWin = new TestQuickWin;
        $quickWin->status = 'suggested';

        $this->assertTrue($quickWin->isPending());
    }

    public function test_quick_win_is_pending_returns_false_for_non_suggested_statuses(): void
    {
        $quickWin = new TestQuickWin;

        $quickWin->status = 'approved';
        $this->assertFalse($quickWin->isPending());

        $quickWin->status = 'rejected';
        $this->assertFalse($quickWin->isPending());
    }
}
