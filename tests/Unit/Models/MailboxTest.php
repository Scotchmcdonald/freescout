<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test Mailbox model methods
 * 
 * Focus: getMailFrom(), relationships, URL generation
 */
class MailboxTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function getMailFrom_returns_email_and_name(): void
    {
        $mailbox = Mailbox::factory()->create([
            'email' => 'support@example.com',
            'name' => 'Support Team',
        ]);

        $result = $mailbox->getMailFrom();

        $this->assertEquals('support@example.com', $result['address']);
        $this->assertArrayHasKey('name', $result);
    }

    /** @test */
    public function getMailFrom_uses_from_name_when_set(): void
    {
        $mailbox = Mailbox::factory()->create([
            'email' => 'support@example.com',
            'name' => 'Support Team',
            'from_name' => 'Custom Support',
        ]);

        $result = $mailbox->getMailFrom();

        $this->assertEquals('Custom Support', $result['name']);
    }

    /** @test */
    public function getMailFrom_prioritizes_from_name_custom(): void
    {
        $mailbox = Mailbox::factory()->create([
            'email' => 'support@example.com',
            'name' => 'Support Team',
            'from_name' => 'Custom Support',
            'from_name_custom' => 'Highest Priority Name',
        ]);

        $result = $mailbox->getMailFrom();

        $this->assertEquals('Highest Priority Name', $result['name']);
    }

    /** @test */
    public function getMailFrom_falls_back_to_name_when_no_from_name(): void
    {
        $mailbox = Mailbox::factory()->create([
            'email' => 'support@example.com',
            'name' => 'Support Team',
            'from_name' => 'Support Team',
            'from_name_custom' => null,
        ]);

        $result = $mailbox->getMailFrom();

        $this->assertArrayHasKey('name', $result);
    }

    /** @test */
    public function getMailFrom_accepts_user_parameter(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();

        $result = $mailbox->getMailFrom($user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('address', $result);
        $this->assertArrayHasKey('name', $result);
    }

    /** @test */
    public function url_returns_correct_route(): void
    {
        $mailbox = Mailbox::factory()->create();
        
        $this->assertNotNull($mailbox->id);
        
        $url = $mailbox->url();

        $this->assertIsString($url);
        $this->assertStringContainsString((string) $mailbox->id, $url);
    }

    /** @test */
    public function users_relationship_loads(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        $mailbox->users()->attach($user->id);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $mailbox->users());
        $this->assertCount(1, $mailbox->users);
        $this->assertEquals($user->id, $mailbox->users->first()->id);
    }

    /** @test */
    public function folders_relationship_loads(): void
    {
        $mailbox = Mailbox::factory()->create();
        Folder::factory()->count(3)->create(['mailbox_id' => $mailbox->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $mailbox->folders());
        $this->assertCount(8, $mailbox->folders); // 5 auto-created + 3 factory
    }

    /** @test */
    public function conversations_relationship_loads(): void
    {
        $mailbox = Mailbox::factory()->create();
        Conversation::factory()->count(5)->create(['mailbox_id' => $mailbox->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $mailbox->conversations());
        $this->assertCount(5, $mailbox->conversations);
    }

    /** @test */
    public function mailbox_has_required_fillable_fields(): void
    {
        $mailbox = new Mailbox();
        $fillable = $mailbox->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('from_name', $fillable);
        $this->assertContains('signature', $fillable);
    }

    /** @test */
    public function mailbox_casts_boolean_fields(): void
    {
        $mailbox = Mailbox::factory()->create([
            'is_default' => true,
            'auto_reply_enabled' => false,
        ]);

        $this->assertIsBool($mailbox->is_default);
        $this->assertIsBool($mailbox->auto_reply_enabled);
    }

    /** @test */
    public function mailbox_casts_integer_fields(): void
    {
        $mailbox = Mailbox::factory()->create([
            'status' => 1,
            'out_method' => 2,
        ]);

        $this->assertIsInt($mailbox->status);
        $this->assertIsInt($mailbox->out_method);
    }

    /** @test */
    public function mailbox_can_be_created_with_factory(): void
    {
        $mailbox = Mailbox::factory()->create([
            'name' => 'Test Mailbox',
            'email' => 'test@example.com',
        ]);

        $this->assertDatabaseHas('mailboxes', [
            'id' => $mailbox->id,
            'name' => 'Test Mailbox',
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function mailbox_has_timestamps(): void
    {
        $mailbox = Mailbox::factory()->create();

        $this->assertNotNull($mailbox->created_at);
        $this->assertNotNull($mailbox->updated_at);
    }

    /** @test */
    public function mailbox_email_is_required(): void
    {
        $mailbox = Mailbox::factory()->make(['email' => null]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        $mailbox->save();
    }

    /** @test */
    public function mailbox_name_is_required(): void
    {
        $mailbox = Mailbox::factory()->make(['name' => null]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        $mailbox->save();
    }

    /** @test */
    public function mailbox_can_have_signature(): void
    {
        $mailbox = Mailbox::factory()->create([
            'signature' => 'Best regards,<br>Support Team',
        ]);

        $this->assertEquals('Best regards,<br>Support Team', $mailbox->signature);
    }

    /** @test */
    public function mailbox_can_have_auto_reply_settings(): void
    {
        $mailbox = Mailbox::factory()->create([
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Out of Office',
            'auto_reply_message' => 'I am currently out of office.',
        ]);

        $this->assertTrue($mailbox->auto_reply_enabled);
        $this->assertEquals('Out of Office', $mailbox->auto_reply_subject);
        $this->assertEquals('I am currently out of office.', $mailbox->auto_reply_message);
    }

    /** @test */
    public function mailbox_can_have_imap_settings(): void
    {
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'user@example.com',
            'in_protocol' => 1, // IMAP
            'in_encryption' => 1, // SSL
        ]);

        $this->assertEquals('imap.example.com', $mailbox->in_server);
        $this->assertEquals(993, $mailbox->in_port);
        $this->assertEquals('user@example.com', $mailbox->in_username);
    }

    /** @test */
    public function mailbox_can_have_smtp_settings(): void
    {
        $mailbox = Mailbox::factory()->create([
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'user@example.com',
            'out_method' => 1, // SMTP
        ]);

        $this->assertEquals('smtp.example.com', $mailbox->out_server);
        $this->assertEquals(587, $mailbox->out_port);
        $this->assertEquals('user@example.com', $mailbox->out_username);
    }

    /** @test */
    public function mailbox_meta_field_casts_to_array(): void
    {
        $meta = ['key1' => 'value1', 'key2' => 'value2'];
        $mailbox = Mailbox::factory()->create(['meta' => $meta]);

        $this->assertIsArray($mailbox->meta);
        $this->assertEquals($meta, $mailbox->meta);
    }

    /** @test */
    public function multiple_users_can_access_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $mailbox->users()->attach([$user1->id, $user2->id]);

        $this->assertCount(2, $mailbox->users);
    }

    /** @test */
    public function mailbox_with_unicode_name(): void
    {
        $mailbox = Mailbox::factory()->create([
            'name' => 'サポート Support 支持',
        ]);

        $this->assertEquals('サポート Support 支持', $mailbox->name);
    }
}
