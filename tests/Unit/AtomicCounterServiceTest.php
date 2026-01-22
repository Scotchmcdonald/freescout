<?php

namespace Tests\Unit;

use App\Services\AtomicCounterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AtomicCounterServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected AtomicCounterService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AtomicCounterService();
        
        // Create test counter
        DB::table('test_counters')->insert([
            'id' => 1,
            'count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    public function test_increment_increases_counter(): void
    {
        $newValue = $this->service->increment('test_counters', ['id' => 1], 'count');
        
        $this->assertEquals(1, $newValue);
        
        $dbValue = DB::table('test_counters')->where('id', 1)->value('count');
        $this->assertEquals(1, $dbValue);
    }
    
    public function test_increment_by_custom_amount(): void
    {
        $newValue = $this->service->increment('test_counters', ['id' => 1], 'count', 5);
        
        $this->assertEquals(5, $newValue);
    }
    
    public function test_decrement_decreases_counter(): void
    {
        // Set initial value to 10
        DB::table('test_counters')->where('id', 1)->update(['count' => 10]);
        
        $newValue = $this->service->decrement('test_counters', ['id' => 1], 'count');
        
        $this->assertEquals(9, $newValue);
    }
    
    public function test_decrement_by_custom_amount(): void
    {
        DB::table('test_counters')->where('id', 1)->update(['count' => 100]);
        
        $newValue = $this->service->decrement('test_counters', ['id' => 1], 'count', 25);
        
        $this->assertEquals(75, $newValue);
    }
    
    public function test_get_returns_current_value(): void
    {
        DB::table('test_counters')->where('id', 1)->update(['count' => 42]);
        
        $value = $this->service->get('test_counters', ['id' => 1], 'count');
        
        $this->assertEquals(42, $value);
    }
    
    public function test_get_returns_zero_for_nonexistent(): void
    {
        $value = $this->service->get('test_counters', ['id' => 999], 'count');
        
        $this->assertEquals(0, $value);
    }
    
    public function test_set_updates_counter_value(): void
    {
        $this->service->set('test_counters', ['id' => 1], 'count', 100);
        
        $value = DB::table('test_counters')->where('id', 1)->value('count');
        $this->assertEquals(100, $value);
    }
    
    public function test_multiple_increments(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->service->increment('test_counters', ['id' => 1], 'count');
        }
        
        $value = $this->service->get('test_counters', ['id' => 1], 'count');
        $this->assertEquals(10, $value);
    }
    
    public function test_increment_throws_on_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->increment('test_counters', ['id' => 1], 'count', -5);
    }
    
    public function test_decrement_throws_on_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->decrement('test_counters', ['id' => 1], 'count', -5);
    }
    
    public function test_works_with_multiple_where_conditions(): void
    {
        // Create counter with composite key
        DB::statement('CREATE TEMPORARY TABLE temp_counters (
            client_id INT,
            type VARCHAR(50),
            count INT DEFAULT 0
        )');
        
        DB::table('temp_counters')->insert([
            'client_id' => 1,
            'type' => 'chromebook',
            'count' => 0,
        ]);
        
        $newValue = $this->service->increment(
            'temp_counters',
            ['client_id' => 1, 'type' => 'chromebook'],
            'count',
            3
        );
        
        $this->assertEquals(3, $newValue);
    }
}
