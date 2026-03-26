<?php

declare(strict_types=1);

namespace Tests\Unit\Crm;

use Modules\Crm\Models\ClientConversation;
use Tests\PureUnitTestCase;

if (! class_exists(StubClientConversation::class)) {
final class StubClientConversation extends ClientConversation
{
    protected static function booted(): void {}

    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}
}


final class ClientConversationTest extends PureUnitTestCase
{
    // ── LINKED_VIA and CATEGORY constants ─────────────────────────────

    public function test_linked_via_constants_are_distinct(): void
    {
        $constants = [
            ClientConversation::LINKED_VIA_EMAIL_MATCH,
            ClientConversation::LINKED_VIA_MANUAL,
            ClientConversation::LINKED_VIA_API,
            ClientConversation::LINKED_VIA_CONTACT_LOOKUP,
        ];
        $this->assertSame(count($constants), count(array_unique($constants)));
    }

    public function test_category_constants_are_distinct(): void
    {
        $constants = [
            ClientConversation::CATEGORY_INCLUDED,
            ClientConversation::CATEGORY_AD_HOC,
            ClientConversation::CATEGORY_EMERGENCY,
        ];
        $this->assertSame(count($constants), count(array_unique($constants)));
    }

    // ── isOpen ────────────────────────────────────────────────────────

    public function test_is_open_returns_true_when_closed_at_is_null(): void
    {
        $conv = new StubClientConversation();
        $conv->setRawAttributes(['closed_at' => null]);
        $this->assertTrue($conv->isOpen());
    }

    public function test_is_open_returns_false_when_closed_at_is_set(): void
    {
        $conv = new StubClientConversation();
        $conv->setRawAttributes(['closed_at' => '2024-01-15 10:00:00']);
        $this->assertFalse($conv->isOpen());
    }

    // ── getResolutionTimeMinutes ───────────────────────────────────────

    public function test_resolution_time_returns_null_when_not_closed(): void
    {
        $conv = new StubClientConversation();
        $conv->setRawAttributes(['opened_at' => '2024-01-15 09:00:00', 'closed_at' => null]);
        $this->assertNull($conv->getResolutionTimeMinutes());
    }

    public function test_resolution_time_returns_null_when_opened_at_missing(): void
    {
        $conv = new StubClientConversation();
        $conv->setRawAttributes(['opened_at' => null, 'closed_at' => '2024-01-15 10:00:00']);
        $this->assertNull($conv->getResolutionTimeMinutes());
    }

    public function test_resolution_time_returns_difference_in_minutes(): void
    {
        $conv = new StubClientConversation();
        $conv->setRawAttributes([
            'opened_at' => '2024-01-15 09:00:00',
            'closed_at' => '2024-01-15 10:30:00',
        ]);
        $this->assertSame(90, $conv->getResolutionTimeMinutes());
    }
}
