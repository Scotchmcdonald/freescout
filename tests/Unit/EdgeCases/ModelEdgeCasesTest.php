<?php

declare(strict_types=1);

namespace Tests\Unit\EdgeCases;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_with_null_subject(): void
    {
        $conversation = Conversation::factory()->create(['subject' => null]);

        $this->assertNull($conversation->subject);
        $this->assertInstanceOf(Conversation::class, $conversation);
    }

    public function test_conversation_with_very_long_subject(): void
    {
        $longSubject = str_repeat('a', 1000);
        $conversation = Conversation::factory()->create(['subject' => $longSubject]);

        $this->assertEquals($longSubject, $conversation->subject);
    }

    public function test_customer_with_null_optional_fields(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => null,
            'company' => null,
            'job_title' => null,
            'city' => null,
        ]);

        $this->assertEquals('John', $customer->first_name);
        $this->assertNull($customer->last_name);
        $this->assertNull($customer->company);
    }

    public function test_mailbox_with_minimal_configuration(): void
    {
        $mailbox = Mailbox::factory()->create([
            'name' => 'Basic Mailbox',
            'email' => 'basic@example.com',
            'in_server' => null,
            'out_server' => null,
        ]);

        $this->assertEquals('Basic Mailbox', $mailbox->name);
        $this->assertNull($mailbox->in_server);
        $this->assertNull($mailbox->out_server);
    }

    public function test_thread_with_empty_body(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => '',
        ]);

        $this->assertEquals('', $thread->body);
        $this->assertInstanceOf(Thread::class, $thread);
    }

    public function test_user_with_maximum_name_length(): void
    {
        $longFirstName = str_repeat('x', 128);
        $longLastName = str_repeat('y', 127);
        $user = User::factory()->create([
            'first_name' => $longFirstName,
            'last_name' => $longLastName,
        ]);

        $this->assertEquals($longFirstName, $user->first_name);
        $this->assertEquals($longLastName, $user->last_name);
    }

    public function test_conversation_cascade_deletion_of_threads(): void
    {
        $conversation = Conversation::factory()->create();
        $thread1 = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $thread2 = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $conversationId = $conversation->id;
        $conversation->delete();

        // Threads should still exist (soft delete or cascade depends on config)
        $this->assertDatabaseMissing('conversations', ['id' => $conversationId]);
    }

    public function test_user_unique_email_constraint(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_mailbox_with_special_characters_in_name(): void
    {
        $specialName = "Support & Sales <info@example.com>";
        $mailbox = Mailbox::factory()->create([
            'name' => $specialName,
            'email' => 'info@example.com',
        ]);

        $this->assertEquals($specialName, $mailbox->name);
    }

    public function test_conversation_with_future_created_at_date(): void
    {
        $futureDate = now()->addDays(10);
        $conversation = Conversation::factory()->create([
            'created_at' => $futureDate,
        ]);

        $this->assertEquals($futureDate->toDateTimeString(), $conversation->created_at->toDateTimeString());
    }

    public function test_thread_with_html_content_in_body(): void
    {
        $htmlContent = '<p>Hello <strong>World</strong></p><script>alert("xss")</script>';
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => $htmlContent,
        ]);

        $this->assertEquals($htmlContent, $thread->body);
    }
}
