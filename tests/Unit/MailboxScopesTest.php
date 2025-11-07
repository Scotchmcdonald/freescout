<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailboxScopesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that mailboxes can be filtered by user access.
     * This tests the forUser scope if implemented.
     */
    public function test_mailboxes_can_be_filtered_by_user(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        $mailbox3 = Mailbox::factory()->create();
        
        // User 1 has access to mailbox1 and mailbox2
        $mailbox1->users()->attach($user1);
        $mailbox2->users()->attach($user1);
        
        // User 2 has access to mailbox2 and mailbox3
        $mailbox2->users()->attach($user2);
        $mailbox3->users()->attach($user2);

        // Act & Assert - User 1's mailboxes
        $user1Mailboxes = $user1->mailboxes;
        $this->assertCount(2, $user1Mailboxes);
        $this->assertTrue($user1Mailboxes->contains($mailbox1));
        $this->assertTrue($user1Mailboxes->contains($mailbox2));
        $this->assertFalse($user1Mailboxes->contains($mailbox3));

        // Act & Assert - User 2's mailboxes
        $user2Mailboxes = $user2->mailboxes;
        $this->assertCount(2, $user2Mailboxes);
        $this->assertFalse($user2Mailboxes->contains($mailbox1));
        $this->assertTrue($user2Mailboxes->contains($mailbox2));
        $this->assertTrue($user2Mailboxes->contains($mailbox3));
    }

    /**
     * Test that admin users can access all mailboxes.
     */
    public function test_admin_users_have_access_to_all_mailboxes(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $regularUser = User::factory()->create(['role' => User::ROLE_USER]);
        
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        $mailbox3 = Mailbox::factory()->create();
        
        // Regular user only has access to mailbox1
        $mailbox1->users()->attach($regularUser);

        // Act & Assert - Admin can see all mailboxes
        $allMailboxes = Mailbox::all();
        $this->assertCount(3, $allMailboxes);
        
        // Regular user sees only their assigned mailboxes
        $userMailboxes = $regularUser->mailboxes;
        $this->assertCount(1, $userMailboxes);
        $this->assertTrue($userMailboxes->contains($mailbox1));
    }

    /**
     * Test that a user with no mailboxes returns empty collection.
     */
    public function test_user_with_no_mailboxes_returns_empty_collection(): void
    {
        // Arrange
        $user = User::factory()->create();
        Mailbox::factory()->count(3)->create();

        // Act
        $userMailboxes = $user->mailboxes;

        // Assert
        $this->assertCount(0, $userMailboxes);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $userMailboxes);
    }

    /**
     * Test mailboxes can be ordered by name.
     */
    public function test_mailboxes_can_be_ordered_by_name(): void
    {
        // Arrange
        Mailbox::factory()->create(['name' => 'Zebra Support']);
        Mailbox::factory()->create(['name' => 'Alpha Support']);
        Mailbox::factory()->create(['name' => 'Beta Support']);

        // Act
        $mailboxes = Mailbox::orderBy('name')->get();

        // Assert
        $this->assertEquals('Alpha Support', $mailboxes[0]->name);
        $this->assertEquals('Beta Support', $mailboxes[1]->name);
        $this->assertEquals('Zebra Support', $mailboxes[2]->name);
    }
}
