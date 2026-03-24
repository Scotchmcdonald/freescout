<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\SendLog;
use Illuminate\Support\Carbon;
use Tests\PureUnitTestCase;

final class TestSendLogHelper extends SendLog
{
    protected function casts(): array
    {
        return [];
    }
}

class SendLogModelTest extends PureUnitTestCase
{
    private function log(array $attrs = []): TestSendLogHelper
    {
        $l = new TestSendLogHelper;
        foreach ($attrs as $k => $v) {
            $l->{$k} = $v;
        }

        return $l;
    }

    // -------------------------------------------------------------------------
    // isSent — accepted, delivery_success, opened, clicked all count as sent
    // -------------------------------------------------------------------------

    public function test_is_sent_returns_true_for_accepted_status(): void
    {
        $this->assertTrue($this->log(['status' => SendLog::STATUS_ACCEPTED])->isSent());
    }

    public function test_is_sent_returns_true_for_delivery_success_status(): void
    {
        $this->assertTrue($this->log(['status' => SendLog::STATUS_DELIVERY_SUCCESS])->isSent());
    }

    public function test_is_sent_returns_true_for_opened_status(): void
    {
        $this->assertTrue($this->log(['status' => SendLog::STATUS_OPENED])->isSent());
    }

    public function test_is_sent_returns_true_for_clicked_status(): void
    {
        $this->assertTrue($this->log(['status' => SendLog::STATUS_CLICKED])->isSent());
    }

    public function test_is_sent_returns_false_for_send_error_status(): void
    {
        $this->assertFalse($this->log(['status' => SendLog::STATUS_SEND_ERROR])->isSent());
    }

    public function test_is_sent_returns_false_for_delivery_error_status(): void
    {
        $this->assertFalse($this->log(['status' => SendLog::STATUS_DELIVERY_ERROR])->isSent());
    }

    // -------------------------------------------------------------------------
    // isFailed
    // -------------------------------------------------------------------------

    public function test_is_failed_returns_true_for_send_error(): void
    {
        $this->assertTrue($this->log(['status' => SendLog::STATUS_SEND_ERROR])->isFailed());
    }

    public function test_is_failed_returns_true_for_delivery_error(): void
    {
        $this->assertTrue($this->log(['status' => SendLog::STATUS_DELIVERY_ERROR])->isFailed());
    }

    public function test_is_failed_returns_false_for_accepted(): void
    {
        $this->assertFalse($this->log(['status' => SendLog::STATUS_ACCEPTED])->isFailed());
    }

    // -------------------------------------------------------------------------
    // wasOpened / wasClicked
    // -------------------------------------------------------------------------

    public function test_was_opened_returns_true_when_opened_at_is_set(): void
    {
        $this->assertTrue($this->log(['opened_at' => Carbon::now()])->wasOpened());
    }

    public function test_was_opened_returns_false_when_opened_at_is_null(): void
    {
        $this->assertFalse($this->log(['opened_at' => null])->wasOpened());
    }

    public function test_was_clicked_returns_true_when_clicked_at_is_set(): void
    {
        $this->assertTrue($this->log(['clicked_at' => Carbon::now()])->wasClicked());
    }

    public function test_was_clicked_returns_false_when_clicked_at_is_null(): void
    {
        $this->assertFalse($this->log(['clicked_at' => null])->wasClicked());
    }
}
