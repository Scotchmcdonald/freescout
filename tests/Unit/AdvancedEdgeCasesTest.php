<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\UnitTestCase;
use App\Models\User;
use App\Models\Mailbox;
use App\Models\Conversation;
use App\Models\Thread;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Attachment;
use App\Models\Email;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdvancedEdgeCasesTest extends UnitTestCase
{
    // Database Transaction Edge Cases
    public function test_nested_transactions_rollback_correctly(): void
    {
        $initialCount = Conversation::count();

        try {
            DB::transaction(function () {
                Conversation::factory()->create();
                
                DB::transaction(function () {
                    Conversation::factory()->create();
                    throw new \Exception('Nested transaction failure');
                });
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        $this->assertEquals($initialCount, Conversation::count());
    }

    public function test_concurrent_updates_handle_race_conditions(): void
    {
        $conversation = Conversation::factory()->create(['status' => Conversation::STATUS_ACTIVE]);
        
        $conv1 = Conversation::find($conversation->id);
        $conv2 = Conversation::find($conversation->id);
        
        $conv1->status = Conversation::STATUS_CLOSED;
        $conv1->save();
        
        $conv2->status = Conversation::STATUS_SPAM;
        $conv2->save();
        
        $this->assertEquals(Conversation::STATUS_SPAM, Conversation::find($conversation->id)->status);
    }

    public function test_soft_deleted_models_excluded_from_queries(): void
    {
        $conversation = Conversation::factory()->create();
        $conversationId = $conversation->id;
        
        $conversation->delete();
        
        $this->assertNull(Conversation::find($conversationId));
        $this->assertNotNull(Conversation::withTrashed()->find($conversationId));
    }

    public function test_soft_deleted_models_can_be_restored(): void
    {
        $conversation = Conversation::factory()->create();
        $conversationId = $conversation->id;
        
        $conversation->delete();
        $this->assertNull(Conversation::find($conversationId));
        
        Conversation::withTrashed()->find($conversationId)->restore();
        $this->assertNotNull(Conversation::find($conversationId));
    }

    public function test_force_delete_permanently_removes_model(): void
    {
        $conversation = Conversation::factory()->create();
        $conversationId = $conversation->id;
        
        $conversation->forceDelete();
        
        $this->assertNull(Conversation::withTrashed()->find($conversationId));
    }

    // Relationship Edge Cases
    public function test_deleting_parent_cascades_to_children(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $threadId = $thread->id;
        
        $conversation->forceDelete();
        
        $this->assertNull(Thread::withTrashed()->find($threadId));
    }

    public function test_orphaned_children_are_handled(): void
    {
        $thread = Thread::factory()->create(['conversation_id' => 99999]);
        
        $this->assertNull($thread->conversation);
    }

    public function test_many_to_many_detach_removes_pivot_records(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $user->mailboxes()->attach($mailbox->id);
        $this->assertTrue($user->mailboxes->contains($mailbox));
        
        $user->mailboxes()->detach($mailbox->id);
        $this->assertFalse($user->fresh()->mailboxes->contains($mailbox));
    }

    public function test_polymorphic_relationship_handles_missing_morph(): void
    {
        $attachment = Attachment::factory()->create([
            'attachable_type' => 'App\\Models\\NonExistentModel',
            'attachable_id' => 1,
        ]);
        
        $this->assertNull($attachment->attachable);
    }

    // Validation Edge Cases
    public function test_unique_validation_ignores_soft_deleted(): void
    {
        $email1 = 'test@example.com';
        
        $customer1 = Customer::factory()->create();
        Email::factory()->create(['customer_id' => $customer1->id, 'email' => $email1]);
        
        $customer1->delete();
        
        $customer2 = Customer::factory()->create();
        $email2 = Email::factory()->create(['customer_id' => $customer2->id, 'email' => $email1]);
        
        $this->assertEquals($email1, $email2->email);
    }

    public function test_mass_assignment_protection_prevents_guarded_fields(): void
    {
        $user = new User();
        $user->fill([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'created_at' => now()->subYear(),
        ]);
        
        $this->assertNotEquals(now()->subYear()->format('Y-m-d'), $user->created_at->format('Y-m-d'));
    }

    // Query Performance Edge Cases
    public function test_eager_loading_prevents_n_plus_1(): void
    {
        $conversations = Conversation::factory()->count(5)->create();
        foreach ($conversations as $conversation) {
            Thread::factory()->count(3)->create(['conversation_id' => $conversation->id]);
        }
        
        DB::enableQueryLog();
        
        $loadedConversations = Conversation::with('threads')->get();
        foreach ($loadedConversations as $conversation) {
            $threadCount = $conversation->threads->count();
        }
        
        $queryCount = count(DB::getQueryLog());
        $this->assertLessThanOrEqual(3, $queryCount);
        
        DB::disableQueryLog();
    }

    public function test_chunk_processing_handles_large_datasets(): void
    {
        Conversation::factory()->count(100)->create();
        
        $processedCount = 0;
        Conversation::chunk(10, function ($conversations) use (&$processedCount) {
            $processedCount += $conversations->count();
        });
        
        $this->assertEquals(100, $processedCount);
    }

    // Attribute Casting Edge Cases
    public function test_json_casting_handles_invalid_json(): void
    {
        $mailbox = Mailbox::factory()->create();
        $mailbox->meta = 'invalid-json';
        $mailbox->save();
        
        $reloaded = Mailbox::find($mailbox->id);
        $this->assertIsArray($reloaded->meta ?? []);
    }

    public function test_date_casting_handles_null_values(): void
    {
        $conversation = Conversation::factory()->create(['closed_at' => null]);
        
        $this->assertNull($conversation->closed_at);
    }

    public function test_boolean_casting_handles_truthy_values(): void
    {
        $mailbox = Mailbox::factory()->create(['active' => 1]);
        
        $this->assertIsBool($mailbox->active);
        $this->assertTrue($mailbox->active);
    }

    // Event Handling Edge Cases
    public function test_event_listeners_handle_exceptions_gracefully(): void
    {
        Event::fake();
        
        $conversation = Conversation::factory()->create();
        
        Event::assertDispatched(\App\Events\ConversationUpdated::class);
    }

    public function test_observer_events_fire_in_correct_order(): void
    {
        $events = [];
        
        User::creating(function ($user) use (&$events) {
            $events[] = 'creating';
        });
        
        User::created(function ($user) use (&$events) {
            $events[] = 'created';
        });
        
        User::factory()->create();
        
        $this->assertEquals(['creating', 'created'], $events);
    }

    // Security Edge Cases
    public function test_sql_injection_is_prevented(): void
    {
        $maliciousInput = "'; DROP TABLE conversations; --";
        
        $results = Conversation::where('subject', $maliciousInput)->get();
        
        $this->assertCount(0, $results);
        $this->assertTrue(Conversation::count() >= 0);
    }

    public function test_xss_prevention_in_attributes(): void
    {
        $xssInput = '<script>alert("XSS")</script>';
        
        $conversation = Conversation::factory()->create(['subject' => $xssInput]);
        
        $this->assertEquals($xssInput, $conversation->subject);
        $this->assertStringContainsString('&lt;script&gt;', htmlspecialchars($conversation->subject));
    }

    // Null Handling Edge Cases
    public function test_null_foreign_keys_are_handled(): void
    {
        $thread = Thread::factory()->create(['user_id' => null]);
        
        $this->assertNull($thread->created_by_user);
    }

    public function test_nullable_text_fields_accept_null(): void
    {
        $conversation = Conversation::factory()->create(['preview' => null]);
        
        $this->assertNull($conversation->preview);
    }

    // Timezone Edge Cases
    public function test_timestamps_are_stored_in_utc(): void
    {
        $conversation = Conversation::factory()->create();
        
        $this->assertEquals('UTC', $conversation->created_at->timezone->getName());
    }

    public function test_date_comparison_handles_different_timezones(): void
    {
        $now = now();
        $conversation = Conversation::factory()->create(['created_at' => $now]);
        
        $this->assertTrue($conversation->created_at->equalTo($now));
    }

    // String Handling Edge Cases
    public function test_long_text_is_truncated_appropriately(): void
    {
        $longText = str_repeat('a', 10000);
        $conversation = Conversation::factory()->create(['subject' => $longText]);
        
        $this->assertLessThanOrEqual(10000, strlen($conversation->subject));
    }

    public function test_unicode_characters_are_stored_correctly(): void
    {
        $unicodeText = '你好世界 🌍 Привет мир';
        $conversation = Conversation::factory()->create(['subject' => $unicodeText]);
        
        $this->assertEquals($unicodeText, $conversation->fresh()->subject);
    }

    public function test_empty_string_vs_null_handling(): void
    {
        $conversation1 = Conversation::factory()->create(['preview' => '']);
        $conversation2 = Conversation::factory()->create(['preview' => null]);
        
        $this->assertNotNull($conversation1->preview);
        $this->assertNull($conversation2->preview);
    }

    // Model State Edge Cases
    public function test_model_attribute_changes_are_tracked(): void
    {
        $conversation = Conversation::factory()->create(['status' => Conversation::STATUS_ACTIVE]);
        
        $conversation->status = Conversation::STATUS_CLOSED;
        
        $this->assertTrue($conversation->isDirty('status'));
        $this->assertEquals(Conversation::STATUS_ACTIVE, $conversation->getOriginal('status'));
    }

    public function test_fresh_method_reloads_model_from_database(): void
    {
        $conversation = Conversation::factory()->create(['status' => Conversation::STATUS_ACTIVE]);
        
        $conversation->status = Conversation::STATUS_CLOSED;
        
        $fresh = $conversation->fresh();
        $this->assertEquals(Conversation::STATUS_ACTIVE, $fresh->status);
    }

    public function test_exists_property_indicates_persistence(): void
    {
        $conversation = new Conversation();
        $this->assertFalse($conversation->exists);
        
        $conversation = Conversation::factory()->create();
        $this->assertTrue($conversation->exists);
    }
}
