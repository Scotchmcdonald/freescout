<?php

declare(strict_types=1);

namespace Tests\Unit\Crm;

use Illuminate\Support\Carbon;
use Modules\Crm\Models\ClientConversation;
use Tests\PureUnitTestCase;

final class TestClientConversation extends ClientConversation
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }

    public function getAttribute($key): mixed
    {
        if (in_array($key, ['opened_at', 'closed_at'], true)) {
            $value = $this->attributes[$key] ?? null;

            return $value ? Carbon::parse($value) : null;
        }

        return parent::getAttribute($key);
    }
}

class ClientConversationHelperTest extends PureUnitTestCase
{
    private function conversation(?Carbon $openedAt, ?Carbon $closedAt): TestClientConversation
    {
        $conversation = new TestClientConversation;
        $conversation->setRawAttributes([
            'opened_at' => $openedAt?->format('Y-m-d H:i:s'),
            'closed_at' => $closedAt?->format('Y-m-d H:i:s'),
        ], true);

        return $conversation;
    }

    public function test_is_open_returns_true_when_closed_at_is_null(): void
    {
        $conversation = $this->conversation(Carbon::parse('2026-03-24 10:00:00'), null);

        $this->assertTrue($conversation->isOpen());
    }

    public function test_is_open_returns_false_when_closed_at_is_set(): void
    {
        $conversation = $this->conversation(
            Carbon::parse('2026-03-24 10:00:00'),
            Carbon::parse('2026-03-24 10:05:00')
        );

        $this->assertFalse($conversation->isOpen());
    }

    public function test_get_resolution_time_minutes_returns_null_when_opened_at_missing(): void
    {
        $conversation = $this->conversation(null, Carbon::parse('2026-03-24 10:05:00'));

        $this->assertNull($conversation->getResolutionTimeMinutes());
    }

    public function test_get_resolution_time_minutes_returns_null_when_closed_at_missing(): void
    {
        $conversation = $this->conversation(Carbon::parse('2026-03-24 10:00:00'), null);

        $this->assertNull($conversation->getResolutionTimeMinutes());
    }

    public function test_get_resolution_time_minutes_returns_diff_in_minutes_when_both_timestamps_exist(): void
    {
        $conversation = $this->conversation(
            Carbon::parse('2026-03-24 10:00:00'),
            Carbon::parse('2026-03-24 11:15:00')
        );

        $this->assertSame(75, $conversation->getResolutionTimeMinutes());
    }

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

    public function test_service_category_constants_are_distinct(): void
    {
        $constants = [
            ClientConversation::CATEGORY_INCLUDED,
            ClientConversation::CATEGORY_AD_HOC,
            ClientConversation::CATEGORY_EMERGENCY,
        ];

        $this->assertSame(count($constants), count(array_unique($constants)));
    }
}
