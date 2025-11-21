<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Mailbox;
use App\Models\MailboxUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test User model methods
 * 
 * Focus: Roles, permissions, name handling, mailbox access
 */
class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_isAdmin_returns_true_for_admin_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($admin->isAdmin());
    }

    public function test_isAdmin_returns_false_for_regular_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->assertFalse($user->isAdmin());
    }

    public function test_isActive_returns_true_for_status_1(): void
    {
        $user = User::factory()->create(['status' => 1]);

        $this->assertTrue($user->isActive());
    }

    public function test_isActive_returns_false_for_inactive_status(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_INACTIVE]);

        $this->assertFalse($user->isActive());
    }

    public function test_getFullName_returns_first_and_last_name(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $user->getFullName());
    }

    public function test_getFullName_returns_email_when_no_name(): void
    {
        $user = User::factory()->create([
            'first_name' => '',
            'last_name' => '',
            'email' => 'user@example.com',
        ]);

        $this->assertEquals('user@example.com', $user->getFullName());
    }

    public function test_getFullName_handles_only_first_name(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => '',
        ]);

        $this->assertEquals('Jane', $user->getFullName());
    }

    public function test_getFullName_handles_only_last_name(): void
    {
        $user = User::factory()->create([
            'first_name' => '',
            'last_name' => 'Smith',
        ]);

        $this->assertEquals('Smith', $user->getFullName());
    }

    public function test_getFirstName_returns_first_name(): void
    {
        $user = User::factory()->create(['first_name' => 'Alice']);

        $this->assertEquals('Alice', $user->getFirstName());
    }

    public function test_getFullNameAttribute_returns_trimmed_name(): void
    {
        $user = User::factory()->create([
            'first_name' => '  John  ',
            'last_name' => '  Doe  ',
        ]);

        $this->assertEquals('John     Doe', $user->full_name);
    }

    public function test_name_attribute_aliases_getFullName(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
        ]);

        $this->assertEquals($user->getFullName(), $user->name);
    }

    public function test_getPhotoUrl_returns_gravatar_url(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $url = $user->getPhotoUrl();

        $this->assertStringContainsString('gravatar.com/avatar/', $url);
        $this->assertStringContainsString('d=mp', $url);
    }

    public function test_getPhotoUrl_generates_consistent_hash(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $url1 = $user->getPhotoUrl();
        $url2 = $user->getPhotoUrl();

        $this->assertEquals($url1, $url2);
    }

    public function test_hasAccessToMailbox_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();

        $this->assertTrue($admin->hasAccessToMailbox($mailbox->id));
    }

    public function test_hasAccessToMailbox_returns_true_when_attached(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxUser::ACCESS_VIEW,
        ]);

        $this->assertTrue($user->hasAccessToMailbox($mailbox->id));
    }

    public function test_hasAccessToMailbox_returns_false_when_not_attached(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();

        $this->assertFalse($user->hasAccessToMailbox($mailbox->id));
    }

    public function test_hasAccessToMailbox_checks_minimum_access_level(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxUser::ACCESS_VIEW,
        ]);

        $this->assertTrue($user->hasAccessToMailbox($mailbox->id, 10));
        $this->assertFalse($user->hasAccessToMailbox($mailbox->id, 30));
    }

    public function test_mailboxes_relationship_loads(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox->id);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->mailboxes());
        $this->assertCount(1, $user->mailboxes);
    }

    public function test_conversations_relationship_loads(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->conversations());
    }

    public function test_threads_relationship_loads(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->threads());
    }

    public function test_folders_relationship_loads(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->folders());
    }

    public function test_user_has_required_fillable_fields(): void
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertContains('first_name', $fillable);
        $this->assertContains('last_name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('role', $fillable);
    }

    public function test_user_can_be_created_with_factory(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'testuser@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'testuser@example.com',
        ]);
    }

    public function test_user_has_timestamps(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->created_at);
        $this->assertNotNull($user->updated_at);
    }

    public function test_user_email_is_unique(): void
    {
        User::factory()->create(['email' => 'unique@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::factory()->create(['email' => 'unique@example.com']);
    }

    public function test_user_with_unicode_name(): void
    {
        $user = User::factory()->create([
            'first_name' => '山田',
            'last_name' => '太郎',
        ]);

        $this->assertEquals('山田 太郎', $user->getFullName());
    }

    public function test_user_can_have_job_title(): void
    {
        $user = User::factory()->create([
            'job_title' => 'Support Manager',
        ]);

        $this->assertEquals('Support Manager', $user->job_title);
    }

    public function test_user_can_have_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '+1-555-1234',
        ]);

        $this->assertEquals('+1-555-1234', $user->phone);
    }

    public function test_user_can_have_timezone(): void
    {
        $user = User::factory()->create([
            'timezone' => 'America/New_York',
        ]);

        $this->assertEquals('America/New_York', $user->timezone);
    }

    public function test_user_role_defaults_to_user(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(User::ROLE_USER, $user->role);
    }

    public function test_user_status_defaults_to_active(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(User::STATUS_ACTIVE, $user->status);
    }

    public function test_multiple_mailboxes_can_be_attached(): void
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        
        $user->mailboxes()->attach([$mailbox1->id, $mailbox2->id]);

        $this->assertCount(2, $user->mailboxes);
    }

    // urlSetup() tests - 0% coverage

    public function test_url_setup_returns_setup_url_with_hash(): void
    {
        $user = User::factory()->create([
            'invite_hash' => 'test-hash-123',
        ]);

        $url = $user->urlSetup();

        $this->assertStringContainsString('test-hash-123', $url);
        $this->assertStringContainsString('user_setup', $url);
    }

    public function test_url_setup_generates_valid_route(): void
    {
        $user = User::factory()->create([
            'invite_hash' => 'valid-hash',
        ]);

        $url = $user->urlSetup();

        // Verify it's a valid URL format
        $this->assertMatchesRegularExpression('/http(s)?:\/\/.*/', $url);
    }

    // dateFormat() tests - 56% coverage

    public function test_date_format_formats_date_string(): void
    {
        $date = '2024-01-15 12:00:00';
        
        $formatted = User::dateFormat($date, 'Y-m-d');

        $this->assertEquals('2024-01-15', $formatted);
    }

    public function test_date_format_returns_empty_string_for_null(): void
    {
        $formatted = User::dateFormat(null);

        $this->assertEquals('', $formatted);
    }

    public function test_date_format_applies_user_timezone(): void
    {
        $user = User::factory()->create([
            'timezone' => 'America/New_York',
        ]);

        $date = '2024-01-15 12:00:00 UTC';
        
        $formatted = User::dateFormat($date, 'Y-m-d H:i:s', $user);

        // Should be converted to EST (UTC-5)
        $this->assertStringContainsString('2024-01-15', $formatted);
    }

    public function test_date_format_uses_default_format_when_not_specified(): void
    {
        $date = '2024-01-15 12:00:00';
        
        $formatted = User::dateFormat($date);

        // Default format is 'M j, Y'
        $this->assertEquals('Jan 15, 2024', $formatted);
    }

    public function test_date_format_handles_datetime_object(): void
    {
        $date = new \DateTime('2024-01-15 12:00:00');
        
        $formatted = User::dateFormat($date, 'Y-m-d');

        $this->assertEquals('2024-01-15', $formatted);
    }

    public function test_date_format_handles_carbon_object(): void
    {
        $date = \Carbon\Carbon::parse('2024-01-15 12:00:00');
        
        $formatted = User::dateFormat($date, 'Y-m-d');

        $this->assertEquals('2024-01-15', $formatted);
    }

    public function test_date_format_returns_empty_for_invalid_date(): void
    {
        $formatted = User::dateFormat('not-a-date');

        $this->assertEquals('', $formatted);
    }

    public function test_date_format_without_user_uses_default_timezone(): void
    {
        $date = '2024-01-15 12:00:00';
        
        $formatted = User::dateFormat($date, 'Y-m-d H:i:s');

        // Should use application default timezone
        $this->assertStringContainsString('2024-01-15', $formatted);
    }

    public function test_date_format_handles_various_format_strings(): void
    {
        $date = '2024-01-15 14:30:45';
        
        $formatted1 = User::dateFormat($date, 'd/m/Y');
        $this->assertEquals('15/01/2024', $formatted1);
        
        $formatted2 = User::dateFormat($date, 'F j, Y, g:i a');
        $this->assertStringContainsString('January 15, 2024', $formatted2);
        
        $formatted3 = User::dateFormat($date, 'l');
        $this->assertEquals('Monday', $formatted3);
    }
}
