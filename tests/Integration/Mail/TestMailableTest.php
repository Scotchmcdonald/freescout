<?php

declare(strict_types=1);

namespace Tests\Integration\Mail;

use App\Mail\Test;
use App\Models\Mailbox;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Mail;
use Tests\IntegrationTestCase;

class TestMailableTest extends IntegrationTestCase
{
    public function test_mailable_can_be_instantiated(): void
    {
        $mailbox = Mailbox::factory()->create();
        $mailable = new Test($mailbox);

        $this->assertInstanceOf(Test::class, $mailable);
    }

    public function test_mailable_stores_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create(['name' => 'Test Mailbox']);
        $mailable = new Test($mailbox);

        $this->assertEquals($mailbox->id, $mailable->mailbox->id);
        $this->assertEquals('Test Mailbox', $mailable->mailbox->name);
    }

    public function test_envelope_returns_correct_instance(): void
    {
        $mailbox = Mailbox::factory()->create();
        $mailable = new Test($mailbox);

        $envelope = $mailable->envelope();

        $this->assertInstanceOf(Envelope::class, $envelope);
    }

    public function test_envelope_subject_includes_app_name(): void
    {
        config(['app.name' => 'TestApp']);
        $mailbox = Mailbox::factory()->create();
        $mailable = new Test($mailbox);

        $envelope = $mailable->envelope();

        $this->assertStringContainsString('TestApp', $envelope->subject);
    }

    public function test_content_returns_correct_instance(): void
    {
        $mailbox = Mailbox::factory()->create();
        $mailable = new Test($mailbox);

        $content = $mailable->content();

        $this->assertInstanceOf(Content::class, $content);
    }

    public function test_content_uses_correct_view(): void
    {
        $mailbox = Mailbox::factory()->create();
        $mailable = new Test($mailbox);

        $content = $mailable->content();

        $this->assertEquals('emails.user.test', $content->view);
    }

    public function test_mailable_can_be_sent(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create();
        $mailable = new Test($mailbox);

        Mail::to('test@example.com')->send($mailable);

        Mail::assertSent(Test::class);
    }

    public function test_mailable_has_queueable_trait(): void
    {
        $mailbox = Mailbox::factory()->create();
        $mailable = new Test($mailbox);

        $traits = class_uses($mailable);

        $this->assertContains('Illuminate\Bus\Queueable', $traits);
    }

    public function test_mailable_has_serializes_models_trait(): void
    {
        $mailbox = Mailbox::factory()->create();
        $mailable = new Test($mailbox);

        $traits = class_uses($mailable);

        $this->assertContains('Illuminate\Queue\SerializesModels', $traits);
    }

    public function test_mailable_can_be_queued(): void
    {
        Mail::fake();

        $mailbox = Mailbox::factory()->create();
        $mailable = new Test($mailbox);

        Mail::to('test@example.com')->queue($mailable);

        Mail::assertQueued(Test::class);
    }

    public function test_mailable_works_with_different_mailboxes(): void
    {
        Mail::fake();

        $mailbox1 = Mailbox::factory()->create(['name' => 'Mailbox 1']);
        $mailbox2 = Mailbox::factory()->create(['name' => 'Mailbox 2']);

        Mail::to('test1@example.com')->send(new Test($mailbox1));
        Mail::to('test2@example.com')->send(new Test($mailbox2));

        Mail::assertSent(Test::class, 2);
    }
}
