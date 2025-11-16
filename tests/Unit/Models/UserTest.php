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

    /** @test */
    public function isAdmin_returns_true_for_admin_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($admin->isAdmin());
    }

    /** @test */
    public function isAdmin_returns_false_for_regular_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->assertFalse($user->isAdmin());
    }

    /** @test */
    public function isActive_returns_true_for_status_1(): void
    {
        $user = User::factory()->create(['status' => 1]);

        $this->assertTrue($user->isActive());
    }

    /** @test */
    public function isActive_returns_false_for_inactive_status(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_INACTIVE]);

        $this->assertFalse($user->isActive());
    }

    /** @test */
    public function getFullName_returns_first_and_last_name(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John Doe', $user->getFullName());
    }

    /** @test */
    public function getFullName_returns_email_when_no_name(): void
    {
        $user = User::factory()->create([
            'first_name' => '',
            'last_name' => '',
            'email' => 'user@example.com',
        ]);

        $this->assertEquals('user@example.com', $user->getFullName());
    }

    /** @test */
    public function getFullName_handles_only_first_name(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => '',
        ]);

        $this->assertEquals('Jane', $user->getFullName());
    }

    /** @test */
    public function getFullName_handles_only_last_name(): void
    {
        $user = User::factory()->create([
            'first_name' => '',
            'last_name' => 'Smith',
        ]);

        $this->assertEquals('Smith', $user->getFullName());
    }

    /** @test */
    public function getFirstName_returns_first_name(): void
    {
        $user = User::factory()->create(['first_name' => 'Alice']);

        $this->assertEquals('Alice', $user->getFirstName());
    }

    /** @test */
    public function getFullNameAttribute_returns_trimmed_name(): void
    {
        $user = User::factory()->create([
            'first_name' => '  John  ',
            'last_name' => '  Doe  ',
        ]);

        $this->assertEquals('John     Doe', $user->full_name);
    }

    /** @test */
    public function name_attribute_aliases_getFullName(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
        ]);

        $this->assertEquals($user->getFullName(), $user->name);
    }

    /** @test */
    public function getPhotoUrl_returns_gravatar_url(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $url = $user->getPhotoUrl();

        $this->assertStringContainsString('gravatar.com/avatar/', $url);
        $this->assertStringContainsString('d=mp', $url);
    }

    /** @test */
    public function getPhotoUrl_generates_consistent_hash(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $url1 = $user->getPhotoUrl();
        $url2 = $user->getPhotoUrl();

        $this->assertEquals($url1, $url2);
    }

    /** @test */
    public function hasAccessToMailbox_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();

        $this->assertTrue($admin->hasAccessToMailbox($mailbox->id));
    }

    /** @test */
    public function hasAccessToMailbox_returns_true_when_attached(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxUser::ACCESS_VIEW,
        ]);

        $this->assertTrue($user->hasAccessToMailbox($mailbox->id));
    }

    /** @test */
    public function hasAccessToMailbox_returns_false_when_not_attached(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();

        $this->assertFalse($user->hasAccessToMailbox($mailbox->id));
    }

    /** @test */
    public function hasAccessToMailbox_checks_minimum_access_level(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxUser::ACCESS_VIEW,
        ]);

        $this->assertTrue($user->hasAccessToMailbox($mailbox->id, 10));
        $this->assertFalse($user->hasAccessToMailbox($mailbox->id, 30));
    }

    /** @test */
    public function mailboxes_relationship_loads(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox->id);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->mailboxes());
        $this->assertCount(1, $user->mailboxes);
    }

    /** @test */
    public function conversations_relationship_loads(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->conversations());
    }

    /** @test */
    public function threads_relationship_loads(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->threads());
    }

    /** @test */
    public function folders_relationship_loads(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->folders());
    }

    /** @test */
    public function user_has_required_fillable_fields(): void
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertContains('first_name', $fillable);
        $this->assertContains('last_name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('role', $fillable);
    }

    /** @test */
    public function user_can_be_created_with_factory(): void
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

    /** @test */
    public function user_has_timestamps(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->created_at);
        $this->assertNotNull($user->updated_at);
    }

    /** @test */
    public function user_email_is_unique(): void
    {
        User::factory()->create(['email' => 'unique@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::factory()->create(['email' => 'unique@example.com']);
    }

    /** @test */
    public function user_with_unicode_name(): void
    {
        $user = User::factory()->create([
            'first_name' => '山田',
            'last_name' => '太郎',
        ]);

        $this->assertEquals('山田 太郎', $user->getFullName());
    }

    /** @test */
    public function user_can_have_job_title(): void
    {
        $user = User::factory()->create([
            'job_title' => 'Support Manager',
        ]);

        $this->assertEquals('Support Manager', $user->job_title);
    }

    /** @test */
    public function user_can_have_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '+1-555-1234',
        ]);

        $this->assertEquals('+1-555-1234', $user->phone);
    }

    /** @test */
    public function user_can_have_timezone(): void
    {
        $user = User::factory()->create([
            'timezone' => 'America/New_York',
        ]);

        $this->assertEquals('America/New_York', $user->timezone);
    }

    /** @test */
    public function user_role_defaults_to_user(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(User::ROLE_USER, $user->role);
    }

    /** @test */
    public function user_status_defaults_to_active(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(User::STATUS_ACTIVE, $user->status);
    }

    /** @test */
    public function multiple_mailboxes_can_be_attached(): void
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        
        $user->mailboxes()->attach([$mailbox1->id, $mailbox2->id]);

        $this->assertCount(2, $user->mailboxes);
    }
}
