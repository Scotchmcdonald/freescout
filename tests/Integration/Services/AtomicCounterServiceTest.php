<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Services\AtomicCounterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * AtomicCounterService Integration Tests
 * 
 * Tests thread-safe counter operations critical for financial data integrity.
 * The AtomicCounterService provides atomic increment/decrement operations
 * that prevent lost updates under concurrent load.
 * 
 * Critical for:
 * - Billing counter accuracy
 * - Credit balance integrity
 * - Asset count reliability
 */
#[Group('integration')]
#[Group('services')]
#[Group('financial')]
class AtomicCounterServiceTest extends TestCase
{
    use RefreshDatabase;

    private AtomicCounterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(AtomicCounterService::class);
        
        // Drop and create test table for atomic counter tests
        Schema::dropIfExists('test_counters');
        Schema::create('test_counters', function ($table) {
            $table->id();
            $table->integer('entity_id');
            $table->string('counter_type');
            $table->integer('count')->default(0);
            $table->timestamps();
            $table->unique(['entity_id', 'counter_type']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_counters');
        parent::tearDown();
    }

    /**
     * Test increment increases counter.
     */
    public function test_increment_increases_counter(): void
    {
        DB::table('test_counters')->insert([
            'entity_id' => 1,
            'counter_type' => 'assets',
            'count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $newValue = $this->service->increment(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'assets'],
            column: 'count'
        );
        
        $this->assertEquals(1, $newValue);
        
        $dbValue = DB::table('test_counters')
            ->where('entity_id', 1)
            ->where('counter_type', 'assets')
            ->value('count');
        
        $this->assertEquals(1, $dbValue);
    }

    /**
     * Test decrement decreases counter.
     */
    public function test_decrement_decreases_counter(): void
    {
        DB::table('test_counters')->insert([
            'entity_id' => 1,
            'counter_type' => 'assets',
            'count' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $newValue = $this->service->decrement(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'assets'],
            column: 'count'
        );
        
        $this->assertEquals(9, $newValue);
    }

    /**
     * Test get returns current value.
     */
    public function test_get_returns_current_value(): void
    {
        DB::table('test_counters')->insert([
            'entity_id' => 1,
            'counter_type' => 'assets',
            'count' => 42,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $value = $this->service->get(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'assets'],
            column: 'count'
        );
        
        $this->assertEquals(42, $value);
    }

    /**
     * Test get returns zero for nonexistent.
     */
    public function test_get_returns_zero_for_nonexistent(): void
    {
        $value = $this->service->get(
            table: 'test_counters',
            where: ['entity_id' => 999, 'counter_type' => 'nonexistent'],
            column: 'count'
        );
        
        $this->assertEquals(0, $value);
    }

    /**
     * Test increment by custom amount.
     */
    public function test_increment_by_custom_amount(): void
    {
        DB::table('test_counters')->insert([
            'entity_id' => 1,
            'counter_type' => 'credits',
            'count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $newValue = $this->service->increment(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'credits'],
            column: 'count',
            amount: 100
        );
        
        $this->assertEquals(100, $newValue);
    }

    /**
     * Test increment throws on negative amount.
     */
    public function test_increment_throws_on_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Use decrement() for negative amounts');
        
        $this->service->increment(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'assets'],
            column: 'count',
            amount: -5
        );
    }

    /**
     * Test decrement throws on negative amount.
     */
    public function test_decrement_throws_on_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Use increment() for negative amounts');
        
        $this->service->decrement(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'assets'],
            column: 'count',
            amount: -5
        );
    }

    /**
     * Test multiple where conditions work.
     */
    public function test_multiple_where_conditions(): void
    {
        DB::table('test_counters')->insert([
            ['entity_id' => 1, 'counter_type' => 'chromebook', 'count' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['entity_id' => 1, 'counter_type' => 'windows', 'count' => 10, 'created_at' => now(), 'updated_at' => now()],
        ]);
        
        $chromebookCount = $this->service->get(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'chromebook'],
            column: 'count'
        );
        
        $windowsCount = $this->service->get(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'windows'],
            column: 'count'
        );
        
        $this->assertEquals(5, $chromebookCount);
        $this->assertEquals(10, $windowsCount);
    }

    /**
     * Test counter isolation between entities.
     */
    public function test_counter_isolation_between_entities(): void
    {
        DB::table('test_counters')->insert([
            ['entity_id' => 1, 'counter_type' => 'assets', 'count' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['entity_id' => 2, 'counter_type' => 'assets', 'count' => 10, 'created_at' => now(), 'updated_at' => now()],
        ]);
        
        // Increment only entity 1
        $this->service->increment(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'assets'],
            column: 'count'
        );
        
        // Entity 1 should be 6
        $entity1Count = $this->service->get(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'assets'],
            column: 'count'
        );
        
        // Entity 2 should still be 10
        $entity2Count = $this->service->get(
            table: 'test_counters',
            where: ['entity_id' => 2, 'counter_type' => 'assets'],
            column: 'count'
        );
        
        $this->assertEquals(6, $entity1Count);
        $this->assertEquals(10, $entity2Count);
    }

    /**
     * Test sequential operations maintain consistency.
     */
    public function test_sequential_operations_maintain_consistency(): void
    {
        DB::table('test_counters')->insert([
            'entity_id' => 1,
            'counter_type' => 'balance',
            'count' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Perform several operations
        $this->service->increment(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'balance'],
            column: 'count',
            amount: 50
        );
        
        $this->service->decrement(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'balance'],
            column: 'count',
            amount: 30
        );
        
        $this->service->increment(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'balance'],
            column: 'count',
            amount: 10
        );
        
        // 100 + 50 - 30 + 10 = 130
        $finalValue = $this->service->get(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'balance'],
            column: 'count'
        );
        
        $this->assertEquals(130, $finalValue);
    }

    /**
     * Test set updates counter value directly.
     */
    public function test_set_updates_counter_value(): void
    {
        DB::table('test_counters')->insert([
            'entity_id' => 1,
            'counter_type' => 'assets',
            'count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->service->set(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'assets'],
            column: 'count',
            value: 999
        );
        
        $value = $this->service->get(
            table: 'test_counters',
            where: ['entity_id' => 1, 'counter_type' => 'assets'],
            column: 'count'
        );
        
        $this->assertEquals(999, $value);
    }
}
