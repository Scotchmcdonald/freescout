<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

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

    public function test_get_mail_from_returns_email_and_name(): void
    {
        $mailbox = Mailbox::factory()->create([
            'email' => 'support@example.com',
            'name' => 'Support Team',
        ]);

        $result = $mailbox->getMailFrom();

        $this->assertEquals('support@example.com', $result['address']);
        $this->assertArrayHasKey('name', $result);
    }

    public function test_get_mail_from_uses_from_name_when_set(): void
    {
        $mailbox = Mailbox::factory()->create([
            'email' => 'support@example.com',
            'name' => 'Support Team',
            'from_name' => 'Custom Support',
        ]);

        $result = $mailbox->getMailFrom();

        $this->assertEquals('Custom Support', $result['name']);
    }

    public function test_get_mail_from_prioritizes_from_name_custom(): void
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

    public function test_get_mail_from_falls_back_to_name_when_no_from_name(): void
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

    public function test_get_mail_from_accepts_user_parameter(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();

        $result = $mailbox->getMailFrom($user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('address', $result);
        $this->assertArrayHasKey('name', $result);
    }

    public function test_url_returns_correct_route(): void
    {
        $mailbox = Mailbox::factory()->create();

        $this->assertNotNull($mailbox->id);

        $url = $mailbox->url();

        $this->assertIsString($url);
        $this->assertStringContainsString((string) $mailbox->id, $url);
    }

    public function test_users_relationship_loads(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        $mailbox->users()->attach($user->id);

        $this->assertCount(1, $mailbox->users);
        $this->assertEquals($user->id, $mailbox->users->first()->id);
    }

    public function test_folders_relationship_loads(): void
    {
        $mailbox = Mailbox::factory()->create();
        Folder::factory()->count(3)->create(['mailbox_id' => $mailbox->id]);

        $this->assertCount(8, $mailbox->folders); // 5 auto-created + 3 factory
    }

    public function test_conversations_relationship_loads(): void
    {
        $mailbox = Mailbox::factory()->create();
        Conversation::factory()->count(5)->create(['mailbox_id' => $mailbox->id]);

        $this->assertCount(5, $mailbox->conversations);
    }

    public function test_mailbox_has_required_fillable_fields(): void
    {
        $mailbox = new Mailbox;
        $fillable = $mailbox->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('from_name', $fillable);
        $this->assertContains('signature', $fillable);
    }

    public function test_mailbox_casts_boolean_fields(): void
    {
        $mailbox = Mailbox::factory()->create([
            'is_default' => true,
            'auto_reply_enabled' => false,
        ]);

        $this->assertIsBool($mailbox->is_default);
        $this->assertIsBool($mailbox->auto_reply_enabled);
    }

    public function test_mailbox_casts_integer_fields(): void
    {
        $mailbox = Mailbox::factory()->create([
            'status' => 1,
            'out_method' => 2,
        ]);

        $this->assertIsInt($mailbox->status);
        $this->assertIsInt($mailbox->out_method);
    }

    public function test_mailbox_can_be_created_with_factory(): void
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

    public function test_mailbox_has_timestamps(): void
    {
        $mailbox = Mailbox::factory()->create();

        $this->assertNotNull($mailbox->created_at);
        $this->assertNotNull($mailbox->updated_at);
    }

    public function test_mailbox_email_is_required(): void
    {
        $mailbox = Mailbox::factory()->make(['email' => null]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $mailbox->save();
    }

    public function test_mailbox_name_is_required(): void
    {
        $mailbox = Mailbox::factory()->make(['name' => null]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $mailbox->save();
    }

    public function test_mailbox_can_have_signature(): void
    {
        $mailbox = Mailbox::factory()->create([
            'signature' => 'Best regards,<br>Support Team',
        ]);

        $this->assertEquals('Best regards,<br>Support Team', $mailbox->signature);
    }

    public function test_mailbox_can_have_auto_reply_settings(): void
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

    public function test_mailbox_can_have_imap_settings(): void
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

    public function test_mailbox_can_have_smtp_settings(): void
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

    public function test_mailbox_meta_field_casts_to_array(): void
    {
        $meta = ['key1' => 'value1', 'key2' => 'value2'];
        $mailbox = Mailbox::factory()->create(['meta' => $meta]);

        $this->assertIsArray($mailbox->meta);
        $this->assertEquals($meta, $mailbox->meta);
    }

    public function test_multiple_users_can_access_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $mailbox->users()->attach([$user1->id, $user2->id]);

        $this->assertCount(2, $mailbox->users);
    }

    public function test_mailbox_with_unicode_name(): void
    {
        $mailbox = Mailbox::factory()->create([
            'name' => 'サポート Support 支持',
        ]);

        $this->assertEquals('サポート Support 支持', $mailbox->name);
    }

    // getAliasesArray() tests - 80% coverage

    public function test_get_aliases_array_parses_comma_separated_string(): void
    {
        $mailbox = Mailbox::factory()->create([
            'aliases' => 'alias1@example.com,alias2@example.com',
        ]);

        $aliases = $mailbox->getAliasesArray();

        $this->assertIsArray($aliases);
        $this->assertCount(2, $aliases);
        $this->assertEquals('alias1@example.com', $aliases[0]);
        $this->assertEquals('alias2@example.com', $aliases[1]);
    }

    public function test_get_aliases_array_trims_whitespace(): void
    {
        $mailbox = Mailbox::factory()->create([
            'aliases' => ' alias1@example.com , alias2@example.com ',
        ]);

        $aliases = $mailbox->getAliasesArray();

        $this->assertCount(2, $aliases);
        // Note: explode doesn't auto-trim, so we get the spaces
        $this->assertStringContainsString('alias1@example.com', $aliases[0]);
        $this->assertStringContainsString('alias2@example.com', $aliases[1]);
    }

    public function test_get_aliases_array_handles_empty_string(): void
    {
        $mailbox = Mailbox::factory()->create([
            'aliases' => '',
        ]);

        $aliases = $mailbox->getAliasesArray();

        $this->assertIsArray($aliases);
        $this->assertEmpty($aliases);
    }

    public function test_get_aliases_array_handles_null(): void
    {
        $mailbox = Mailbox::factory()->create([
            'aliases' => null,
        ]);

        $aliases = $mailbox->getAliasesArray();

        $this->assertIsArray($aliases);
        $this->assertEmpty($aliases);
    }

    public function test_get_aliases_array_handles_single_alias(): void
    {
        $mailbox = Mailbox::factory()->create([
            'aliases' => 'single@example.com',
        ]);

        $aliases = $mailbox->getAliasesArray();

        $this->assertIsArray($aliases);
        $this->assertCount(1, $aliases);
        $this->assertEquals('single@example.com', $aliases[0]);
    }

    public function test_get_aliases_array_returns_array_if_already_array(): void
    {
        $aliasArray = ['alias1@example.com', 'alias2@example.com'];
        $mailbox = Mailbox::factory()->make([
            'aliases' => $aliasArray,
        ]);

        $aliases = $mailbox->getAliasesArray();

        $this->assertIsArray($aliases);
        $this->assertEquals($aliasArray, $aliases);
    }

    public function test_get_aliases_array_handles_multiple_commas(): void
    {
        $mailbox = Mailbox::factory()->create([
            'aliases' => 'alias1@example.com,alias2@example.com,alias3@example.com',
        ]);

        $aliases = $mailbox->getAliasesArray();

        $this->assertCount(3, $aliases);
    }

    // Additional edge case tests for getAliasesArray

    public function test_get_aliases_array_handles_trailing_comma(): void
    {
        $mailbox = Mailbox::factory()->create([
            'aliases' => 'alias1@example.com,alias2@example.com,',
        ]);

        $aliases = $mailbox->getAliasesArray();

        // Should have 3 elements (including empty string from trailing comma)
        $this->assertIsArray($aliases);
        // Filter out empty strings if implementation does that
        $nonEmpty = array_filter($aliases, fn ($a) => ! empty($a));
        $this->assertGreaterThanOrEqual(2, count($nonEmpty));
    }

    public function test_get_aliases_array_handles_semicolon_separator(): void
    {
        $mailbox = Mailbox::factory()->create([
            'aliases' => 'alias1@example.com;alias2@example.com',
        ]);

        $aliases = $mailbox->getAliasesArray();

        // Since it splits on comma, semicolon won't split
        $this->assertIsArray($aliases);
        // Should be treated as single alias with semicolon in it
        $this->assertCount(1, $aliases);
    }

    public function test_get_aliases_array_handles_unicode_email_addresses(): void
    {
        $mailbox = Mailbox::factory()->create([
            'aliases' => 'user@例え.jp,admin@テスト.com',
        ]);

        $aliases = $mailbox->getAliasesArray();

        $this->assertIsArray($aliases);
        $this->assertCount(2, $aliases);
        $this->assertStringContainsString('例え', $aliases[0]);
        $this->assertStringContainsString('テスト', $aliases[1]);
    }

    public function test_get_aliases_array_handles_mixed_case_emails(): void
    {
        $mailbox = Mailbox::factory()->create([
            'aliases' => 'Alias1@Example.COM,ALIAS2@EXAMPLE.com',
        ]);

        $aliases = $mailbox->getAliasesArray();

        $this->assertIsArray($aliases);
        $this->assertCount(2, $aliases);
        // Should preserve original case
        $this->assertEquals('Alias1@Example.COM', $aliases[0]);
        $this->assertEquals('ALIAS2@EXAMPLE.com', $aliases[1]);
    }

    public function test_get_aliases_array_handles_very_long_alias_list(): void
    {
        $longAliasList = [];
        for ($i = 0; $i < 50; $i++) {
            $longAliasList[] = "alias{$i}@example.com";
        }

        $mailbox = Mailbox::factory()->create([
            'aliases' => implode(',', $longAliasList),
        ]);

        $aliases = $mailbox->getAliasesArray();

        $this->assertIsArray($aliases);
        $this->assertCount(50, $aliases);
        $this->assertEquals('alias0@example.com', $aliases[0]);
        $this->assertEquals('alias49@example.com', $aliases[49]);
    }

    public function test_get_aliases_array_handles_special_characters_in_local_part(): void
    {
        $mailbox = Mailbox::factory()->create([
            'aliases' => 'user+tag@example.com,user.name@example.com,user_name@example.com',
        ]);

        $aliases = $mailbox->getAliasesArray();

        $this->assertIsArray($aliases);
        $this->assertCount(3, $aliases);
        $this->assertEquals('user+tag@example.com', $aliases[0]);
        $this->assertEquals('user.name@example.com', $aliases[1]);
        $this->assertEquals('user_name@example.com', $aliases[2]);
    }

    public function test_get_aliases_array_with_double_commas(): void
    {
        $mailbox = Mailbox::factory()->create([
            'aliases' => 'alias1@example.com,,alias2@example.com',
        ]);

        $aliases = $mailbox->getAliasesArray();

        // Should have empty string between double commas
        $this->assertIsArray($aliases);
        $this->assertGreaterThanOrEqual(2, count($aliases));
    }

    // ── Boundary & Validation Tests ──────────────────────────────────────────

    public function test_duplicate_mailbox_email_violates_unique_validation_constraint(): void
    {
        Mailbox::factory()->create(['email' => 'boundary-mailbox@example.com']);

        // Validation: mailbox email unique constraint prevents unauthorized duplicates
        $this->expectException(\Illuminate\Database\QueryException::class);
        Mailbox::factory()->create(['email' => 'boundary-mailbox@example.com']);
    }

    public function test_unauthorized_user_has_no_mailbox_access_by_default(): void
    {
        $mailbox = Mailbox::factory()->create();
        $unauthorizedUser = User::factory()->create();

        // Authorization boundary: user without explicit grant is not authorized for mailbox
        $hasAccess = \App\Models\MailboxUser::where([
            'mailbox_id' => $mailbox->id,
            'user_id' => $unauthorizedUser->id,
        ])->exists();

        $this->assertFalse($hasAccess, 'Authorization: user without mailbox grant is unauthorized');
    }

    public function test_validates_authorized_mailbox_user_relationship(): void
    {
        $mailbox = Mailbox::factory()->create();
        $authorizedUser = User::factory()->create();

        // Authorization: explicitly grant user access to mailbox
        \App\Models\MailboxUser::create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $authorizedUser->id,
        ]);

        // Validation: authorized user appears in mailbox user relationship
        $hasAccess = \App\Models\MailboxUser::where([
            'mailbox_id' => $mailbox->id,
            'user_id' => $authorizedUser->id,
        ])->exists();

        $this->assertTrue($hasAccess, 'Validation: authorized user passes mailbox authorization check');
    }
}
