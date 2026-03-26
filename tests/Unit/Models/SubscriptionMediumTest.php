<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Subscription;
use Tests\PureUnitTestCase;

if (! class_exists(StubSubscription::class)) {
final class StubSubscription extends Subscription
{
    protected static function booted(): void {}
}
}


final class SubscriptionMediumTest extends PureUnitTestCase
{
    private function make(int $medium): StubSubscription
    {
        $s = new StubSubscription;
        $s->setRawAttributes(['medium' => $medium]);

        return $s;
    }

    public function test_is_email_true_for_medium_1(): void
    {
        $this->assertTrue($this->make(Subscription::MEDIUM_EMAIL)->isEmail());
    }

    public function test_is_email_false_for_browser(): void
    {
        $this->assertFalse($this->make(Subscription::MEDIUM_BROWSER)->isEmail());
    }

    public function test_is_browser_true_for_medium_2(): void
    {
        $this->assertTrue($this->make(Subscription::MEDIUM_BROWSER)->isBrowser());
    }

    public function test_is_browser_false_for_email(): void
    {
        $this->assertFalse($this->make(Subscription::MEDIUM_EMAIL)->isBrowser());
    }

    public function test_is_mobile_true_for_medium_3(): void
    {
        $this->assertTrue($this->make(Subscription::MEDIUM_MOBILE)->isMobile());
    }

    public function test_is_mobile_false_for_browser(): void
    {
        $this->assertFalse($this->make(Subscription::MEDIUM_BROWSER)->isMobile());
    }

    public function test_medium_constants_are_distinct(): void
    {
        $mediums = [
            Subscription::MEDIUM_EMAIL,
            Subscription::MEDIUM_BROWSER,
            Subscription::MEDIUM_MOBILE,
        ];

        $this->assertSame(count($mediums), count(array_unique($mediums)));
    }
}
