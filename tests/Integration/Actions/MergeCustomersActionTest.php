<?php

declare(strict_types=1);

namespace Tests\Integration\Actions;

use App\Actions\MergeCustomersAction;
use App\Models\Conversation;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeCustomersActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_moves_conversations_deduplicates_emails_and_merges_notes(): void
    {
        $source = Customer::factory()->withoutEmail()->create([
            'phones' => [
                ['number' => '111-111-1111', 'type' => 'work'],
                ['number' => '222-222-2222', 'type' => 'mobile'],
            ],
            'notes' => 'Source notes',
        ]);
        $source->emails()->create(['email' => 'source-only@example.com', 'type' => 2]);

        $target = Customer::factory()->withoutEmail()->create([
            'phones' => [
                ['number' => '111-111-1111', 'type' => 'work'],
                ['number' => '333-333-3333', 'type' => 'mobile'],
            ],
            'notes' => 'Target notes',
        ]);
        $target->emails()->create(['email' => 'shared@example.com', 'type' => 1]);

        $conversation = Conversation::factory()->create(['customer_id' => $source->id]);

        $result = (new MergeCustomersAction)->execute($source->fresh(['emails']), $target->fresh(['emails']));

        $this->assertTrue($result);
        $this->assertSame($target->id, $conversation->fresh()->customer_id);
        $this->assertDatabaseMissing('customers', ['id' => $source->id]);
        $this->assertSame(
            ['shared@example.com', 'source-only@example.com'],
            $target->fresh()->emails()->orderBy('email')->pluck('email')->all()
        );
        $this->assertSame([
            ['number' => '111-111-1111', 'type' => 'work'],
            ['number' => '333-333-3333', 'type' => 'mobile'],
            ['number' => '222-222-2222', 'type' => 'mobile'],
        ], $target->fresh()->phones);
        $this->assertStringContainsString('Target notes', (string) $target->fresh()->notes);
        $this->assertStringContainsString('Merged from customer', (string) $target->fresh()->notes);
        $this->assertStringContainsString('Source notes', (string) $target->fresh()->notes);
    }
}
