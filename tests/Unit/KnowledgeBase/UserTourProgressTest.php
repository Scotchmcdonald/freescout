<?php

declare(strict_types=1);

namespace Tests\Unit\KnowledgeBase;

use Modules\KnowledgeBase\Models\UserTourProgress;
use Tests\PureUnitTestCase;

if (! class_exists(StubTourProgress::class)) {
    final class StubTourProgress extends UserTourProgress
    {
        protected static function booted(): void {}
    }
}

final class UserTourProgressTest extends PureUnitTestCase
{
    public function test_is_outdated_returns_true_when_versions_differ(): void
    {
        $p = new StubTourProgress;
        $p->setRawAttributes(['version' => '1.0.0']);
        $this->assertTrue($p->isOutdated('1.0.1'));
    }

    public function test_is_outdated_returns_false_when_versions_match(): void
    {
        $p = new StubTourProgress;
        $p->setRawAttributes(['version' => '2.0.0']);
        $this->assertFalse($p->isOutdated('2.0.0'));
    }
}
