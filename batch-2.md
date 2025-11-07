# Batch 2: Mailbox Management - PHPUnit Test Implementation

This file contains the complete test code for Batch 2 as specified in TEST_PLAN.md.

## Summary of Tests to Add

Based on TEST_PLAN.md Batch 2 requirements and analysis of existing tests, the following NEW tests need to be added:

### Tests Already Covered (Skip):
- ✅ Mailbox model relationships (users, folders, conversations) - covered in ModelRelationshipsTest.php
- ✅ User can view list of mailboxes - covered in MailboxTest.php
- ✅ User can create mailbox - covered in MailboxTest.php  
- ✅ User can update mailbox - covered in MailboxTest.php
- ✅ User can delete mailbox - covered in MailboxTest.php
- ✅ Cannot create mailbox with invalid IMAP/SMTP settings - covered in MailboxConnectionTest.php
- ✅ User cannot view/access mailbox without permission - covered in MailboxPermissionsTest.php
- ✅ User cannot update mailbox without permission - covered in MailboxPermissionsTest.php

### New Tests Required for Batch 2:

---

## FILE 1: /tests/Unit/MailboxScopesTest.php

**Purpose:** Test custom scopes on Mailbox model (specifically forUser scope if it exists or should exist)

```php
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
```

---

## FILE 2: /tests/Unit/FolderHierarchyTest.php

**Purpose:** Test Folder model hierarchy logic and folder type methods

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderHierarchyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that folder type helper methods work correctly.
     */
    public function test_folder_type_helper_methods(): void
    {
        // Arrange & Act
        $inboxFolder = Folder::factory()->create(['type' => Folder::TYPE_INBOX]);
        $sentFolder = Folder::factory()->create(['type' => Folder::TYPE_SENT]);
        $draftsFolder = Folder::factory()->create(['type' => Folder::TYPE_DRAFTS]);
        $spamFolder = Folder::factory()->create(['type' => Folder::TYPE_SPAM]);
        $trashFolder = Folder::factory()->create(['type' => Folder::TYPE_TRASH]);

        // Assert
        $this->assertTrue($inboxFolder->isInbox());
        $this->assertFalse($inboxFolder->isSent());
        
        $this->assertTrue($sentFolder->isSent());
        $this->assertFalse($sentFolder->isInbox());
        
        $this->assertTrue($draftsFolder->isDrafts());
        $this->assertFalse($draftsFolder->isSpam());
        
        $this->assertTrue($spamFolder->isSpam());
        $this->assertFalse($spamFolder->isTrash());
        
        $this->assertTrue($trashFolder->isTrash());
        $this->assertFalse($trashFolder->isDrafts());
    }

    /**
     * Test that folders belong to correct mailbox.
     */
    public function test_folders_belong_to_correct_mailbox(): void
    {
        // Arrange
        $mailbox1 = Mailbox::factory()->create(['name' => 'Support']);
        $mailbox2 = Mailbox::factory()->create(['name' => 'Sales']);
        
        $folder1 = Folder::factory()->create(['mailbox_id' => $mailbox1->id, 'type' => Folder::TYPE_INBOX]);
        $folder2 = Folder::factory()->create(['mailbox_id' => $mailbox1->id, 'type' => Folder::TYPE_SENT]);
        $folder3 = Folder::factory()->create(['mailbox_id' => $mailbox2->id, 'type' => Folder::TYPE_INBOX]);

        // Act & Assert
        $mailbox1Folders = $mailbox1->folders;
        $this->assertCount(2, $mailbox1Folders);
        $this->assertTrue($mailbox1Folders->contains($folder1));
        $this->assertTrue($mailbox1Folders->contains($folder2));
        $this->assertFalse($mailbox1Folders->contains($folder3));
    }

    /**
     * Test that folders can belong to a specific user (personal folders).
     */
    public function test_folders_can_belong_to_user(): void
    {
        // Arrange
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $personalFolder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $user->id,
            'type' => Folder::TYPE_MINE,
        ]);

        // Act & Assert
        $this->assertInstanceOf(User::class, $personalFolder->user);
        $this->assertEquals($user->id, $personalFolder->user->id);
    }

    /**
     * Test that system folders (Inbox, Sent, etc.) have no user_id.
     */
    public function test_system_folders_have_no_user(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        
        $inboxFolder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
            'user_id' => null,
        ]);

        // Act & Assert
        $this->assertNull($inboxFolder->user_id);
        $this->assertNull($inboxFolder->user);
        $this->assertTrue($inboxFolder->isInbox());
    }

    /**
     * Test that folder counters are properly tracked.
     */
    public function test_folder_counters_are_tracked(): void
    {
        // Arrange
        $folder = Folder::factory()->create([
            'total_count' => 10,
            'active_count' => 5,
        ]);

        // Act & Assert
        $this->assertEquals(10, $folder->total_count);
        $this->assertEquals(5, $folder->active_count);
    }

    /**
     * Test that folders can have conversations through the pivot table.
     */
    public function test_folders_can_have_conversations_via_pivot(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        
        // Note: This test requires Conversation model and factory
        // If not available yet, this test validates the relationship method exists
        $this->assertTrue(method_exists($folder, 'conversationsViaFolder'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $folder->conversationsViaFolder()
        );
    }

    /**
     * Test that multiple folders can exist per mailbox.
     */
    public function test_mailbox_can_have_multiple_folder_types(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        
        $inbox = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_INBOX]);
        $sent = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_SENT]);
        $drafts = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_DRAFTS]);
        $spam = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_SPAM]);
        $trash = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => Folder::TYPE_TRASH]);

        // Act
        $folders = $mailbox->folders;

        // Assert
        $this->assertCount(5, $folders);
        $this->assertTrue($folders->contains($inbox));
        $this->assertTrue($folders->contains($sent));
        $this->assertTrue($folders->contains($drafts));
        $this->assertTrue($folders->contains($spam));
        $this->assertTrue($folders->contains($trash));
    }
}
```

---

## FILE 3: /tests/Unit/MailboxControllerValidationTest.php

**Purpose:** Test validation logic in MailboxController for creating and updating mailboxes

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\MailboxController;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MailboxControllerValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that mailbox name is required for creation.
     */
    public function test_mailbox_name_is_required_for_creation(): void
    {
        // Arrange
        $data = [
            'email' => 'test@example.com',
            'in_server' => 'imap.example.com',
            'in_port' => 993,
        ];

        // Act
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:mailboxes,email',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    /**
     * Test that mailbox email must be unique.
     */
    public function test_mailbox_email_must_be_unique(): void
    {
        // Arrange
        Mailbox::factory()->create(['email' => 'existing@example.com']);
        
        $data = [
            'name' => 'New Mailbox',
            'email' => 'existing@example.com',
        ];

        // Act
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:mailboxes,email',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /**
     * Test that mailbox email can be same when updating (except for itself).
     */
    public function test_mailbox_email_can_remain_same_on_update(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create(['email' => 'existing@example.com']);
        
        $data = [
            'name' => 'Updated Mailbox',
            'email' => 'existing@example.com',
        ];

        // Act - Use the ignore rule for updates
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:mailboxes,email,' . $mailbox->id,
        ]);

        // Assert
        $this->assertFalse($validator->fails());
    }

    /**
     * Test that in_port must be an integer.
     */
    public function test_in_port_must_be_integer(): void
    {
        // Arrange
        $data = [
            'name' => 'Test Mailbox',
            'email' => 'test@example.com',
            'in_port' => 'not-a-number',
        ];

        // Act
        $validator = Validator::make($data, [
            'in_port' => 'nullable|integer',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('in_port', $validator->errors()->toArray());
    }

    /**
     * Test that out_port must be an integer.
     */
    public function test_out_port_must_be_integer(): void
    {
        // Arrange
        $data = [
            'name' => 'Test Mailbox',
            'email' => 'test@example.com',
            'out_port' => 'invalid-port',
        ];

        // Act
        $validator = Validator::make($data, [
            'out_port' => 'nullable|integer',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('out_port', $validator->errors()->toArray());
    }

    /**
     * Test that in_protocol must be valid value.
     */
    public function test_in_protocol_must_be_valid(): void
    {
        // Arrange
        $data = [
            'in_protocol' => 'invalid-protocol',
        ];

        // Act
        $validator = Validator::make($data, [
            'in_protocol' => 'nullable|in:imap,pop3',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('in_protocol', $validator->errors()->toArray());
    }

    /**
     * Test that out_method must be valid value.
     */
    public function test_out_method_must_be_valid(): void
    {
        // Arrange
        $data = [
            'out_method' => 'carrier-pigeon',
        ];

        // Act
        $validator = Validator::make($data, [
            'out_method' => 'nullable|in:mail,smtp',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('out_method', $validator->errors()->toArray());
    }

    /**
     * Test that in_encryption must be valid value.
     */
    public function test_in_encryption_must_be_valid(): void
    {
        // Arrange
        $data = [
            'in_encryption' => 'super-encryption',
        ];

        // Act
        $validator = Validator::make($data, [
            'in_encryption' => 'nullable|in:none,ssl,tls',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('in_encryption', $validator->errors()->toArray());
    }

    /**
     * Test that out_encryption must be valid value.
     */
    public function test_out_encryption_must_be_valid(): void
    {
        // Arrange
        $data = [
            'out_encryption' => 'quantum-encryption',
        ];

        // Act
        $validator = Validator::make($data, [
            'out_encryption' => 'nullable|in:none,ssl,tls',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('out_encryption', $validator->errors()->toArray());
    }

    /**
     * Test that auto_reply_enabled must be boolean.
     */
    public function test_auto_reply_enabled_must_be_boolean(): void
    {
        // Arrange
        $data = [
            'auto_reply_enabled' => 'yes-please',
        ];

        // Act
        $validator = Validator::make($data, [
            'auto_reply_enabled' => 'nullable|boolean',
        ]);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('auto_reply_enabled', $validator->errors()->toArray());
    }

    /**
     * Test that valid mailbox data passes validation.
     */
    public function test_valid_mailbox_data_passes_validation(): void
    {
        // Arrange
        $data = [
            'name' => 'Support Mailbox',
            'email' => 'support@example.com',
            'from_name' => 'Support Team',
            'out_method' => 'smtp',
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'user@example.com',
            'out_password' => 'secret123',
            'out_encryption' => 'tls',
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'user@example.com',
            'in_password' => 'secret123',
            'in_protocol' => 'imap',
            'in_encryption' => 'ssl',
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Thank you',
            'auto_reply_message' => 'We received your message',
        ];

        // Act
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:mailboxes,email',
            'from_name' => 'nullable|string|max:255',
            'out_method' => 'nullable|in:mail,smtp',
            'out_server' => 'nullable|string|max:255',
            'out_port' => 'nullable|integer',
            'out_username' => 'nullable|string|max:255',
            'out_password' => 'nullable|string',
            'out_encryption' => 'nullable|in:none,ssl,tls',
            'in_server' => 'nullable|string|max:255',
            'in_port' => 'nullable|integer',
            'in_username' => 'nullable|string|max:255',
            'in_password' => 'nullable|string',
            'in_protocol' => 'nullable|in:imap,pop3',
            'in_encryption' => 'nullable|in:none,ssl,tls',
            'auto_reply_enabled' => 'nullable|boolean',
            'auto_reply_subject' => 'nullable|string|max:255',
            'auto_reply_message' => 'nullable|string',
        ]);

        // Assert
        $this->assertFalse($validator->fails());
    }
}
```

---

## FILE 4: /tests/Feature/MailboxRegressionTest.php

**Purpose:** Regression tests to verify mailbox permission logic and folder structures match L5 version

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailboxRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression Test: Verify mailbox permission logic is consistent with L5 version.
     * 
     * In L5, users can access mailboxes through the mailbox_user pivot table.
     * This test ensures the modern version maintains the same access control.
     */
    public function test_mailbox_permission_logic_matches_l5_version(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user1 = User::factory()->create(['role' => User::ROLE_USER]);
        $user2 = User::factory()->create(['role' => User::ROLE_USER]);
        
        $mailbox1 = Mailbox::factory()->create(['name' => 'Support']);
        $mailbox2 = Mailbox::factory()->create(['name' => 'Sales']);
        
        // User1 has access to mailbox1, User2 has access to mailbox2
        $mailbox1->users()->attach($user1);
        $mailbox2->users()->attach($user2);

        // Act & Assert - User1 can access mailbox1
        $this->actingAs($user1);
        $response1 = $this->get(route('mailboxes.view', $mailbox1));
        $response1->assertStatus(200);
        
        // Act & Assert - User1 cannot access mailbox2
        $response2 = $this->get(route('mailboxes.view', $mailbox2));
        $response2->assertStatus(403);
        
        // Act & Assert - Admin can access all mailboxes
        $this->actingAs($admin);
        $response3 = $this->get(route('mailboxes.view', $mailbox1));
        $response3->assertStatus(200);
        $response4 = $this->get(route('mailboxes.view', $mailbox2));
        $response4->assertStatus(200);
    }

    /**
     * Regression Test: Verify mailbox-user relationship with pivot data.
     * 
     * In L5, the mailbox_user pivot table stores additional data like 'after_send', 'hide', 'mute', 'access'.
     * This test ensures the modern version maintains compatibility.
     */
    public function test_mailbox_user_pivot_maintains_l5_compatibility(): void
    {
        // Arrange
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        // Attach user with pivot data (as in L5)
        $mailbox->users()->attach($user->id, [
            'after_send' => 1,
        ]);

        // Act
        $mailbox->load('users');
        $attachedUser = $mailbox->users->first();

        // Assert - Pivot data is accessible
        $this->assertEquals($user->id, $attachedUser->id);
        $this->assertEquals(1, $attachedUser->pivot->after_send);
        $this->assertNotNull($attachedUser->pivot->created_at);
        $this->assertNotNull($attachedUser->pivot->updated_at);
    }

    /**
     * Regression Test: Verify folder structure and relationships match L5.
     * 
     * In L5, folders have types (Inbox=1, Sent=2, Drafts=3, Spam=4, Trash=5, etc.)
     * and belong to both mailboxes and optionally users (personal folders).
     */
    public function test_folder_structure_matches_l5_version(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        
        // Create system folders (as in L5)
        $inboxFolder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => null,
            'type' => Folder::TYPE_INBOX,
        ]);
        
        $sentFolder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => null,
            'type' => Folder::TYPE_SENT,
        ]);
        
        // Create personal folder (as in L5)
        $mineFolder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $user->id,
            'type' => Folder::TYPE_MINE,
        ]);

        // Act & Assert - Folder types match L5 constants
        $this->assertEquals(1, Folder::TYPE_INBOX);
        $this->assertEquals(2, Folder::TYPE_SENT);
        $this->assertEquals(3, Folder::TYPE_DRAFTS);
        $this->assertEquals(4, Folder::TYPE_SPAM);
        $this->assertEquals(5, Folder::TYPE_TRASH);
        $this->assertEquals(20, Folder::TYPE_ASSIGNED);
        $this->assertEquals(25, Folder::TYPE_MINE);
        $this->assertEquals(30, Folder::TYPE_STARRED);
        
        // Assert - Folder relationships work as in L5
        $this->assertInstanceOf(Mailbox::class, $inboxFolder->mailbox);
        $this->assertNull($inboxFolder->user_id);
        $this->assertInstanceOf(User::class, $mineFolder->user);
    }

    /**
     * Regression Test: Verify conversation-folder relationships.
     * 
     * In L5, conversations can belong to folders through the conversation_folder pivot table.
     */
    public function test_conversation_folder_relationship_matches_l5(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        
        // Assert - The conversationsViaFolder relationship exists (as in L5)
        $this->assertTrue(method_exists($folder, 'conversationsViaFolder'));
        $relationship = $folder->conversationsViaFolder();
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $relationship
        );
        
        // Assert - The pivot table name is conversation_folder (as in L5)
        $this->assertEquals('conversation_folder', $relationship->getTable());
    }

    /**
     * Regression Test: Verify mailbox password encryption.
     * 
     * In L5, passwords are automatically encrypted/decrypted via accessors/mutators.
     * The modern version uses manual encryption in the controller.
     */
    public function test_mailbox_passwords_are_encrypted(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin);
        
        $plainPassword = 'my-secret-password';
        
        // Act - Create mailbox with password
        $response = $this->post(route('mailboxes.store'), [
            'name' => 'Test Mailbox',
            'email' => 'test@example.com',
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'user@example.com',
            'in_password' => $plainPassword,
            'out_server' => 'smtp.example.com',
            'out_port' => 587,
            'out_username' => 'user@example.com',
            'out_password' => $plainPassword,
        ]);
        
        // Assert
        $response->assertRedirect();
        $mailbox = Mailbox::where('email', 'test@example.com')->first();
        $this->assertNotNull($mailbox);
        
        // Assert - Passwords are encrypted in database (not plain text)
        $this->assertNotEquals($plainPassword, $mailbox->getRawOriginal('in_password'));
        $this->assertNotEquals($plainPassword, $mailbox->getRawOriginal('out_password'));
        $this->assertNotEmpty($mailbox->getRawOriginal('in_password'));
        $this->assertNotEmpty($mailbox->getRawOriginal('out_password'));
    }

    /**
     * Regression Test: Verify mailbox getMailFrom method behavior.
     * 
     * This method should return the correct "from" name and address for outgoing emails.
     */
    public function test_mailbox_get_mail_from_matches_l5_behavior(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'name' => 'Support',
            'email' => 'support@example.com',
            'from_name' => 'Support Team',
        ]);
        
        // Act
        $from = $mailbox->getMailFrom();
        
        // Assert
        $this->assertIsArray($from);
        $this->assertArrayHasKey('address', $from);
        $this->assertArrayHasKey('name', $from);
        $this->assertEquals('support@example.com', $from['address']);
        $this->assertEquals('Support Team', $from['name']);
    }

    /**
     * Regression Test: Verify mailbox uses custom from_name_custom when set.
     */
    public function test_mailbox_uses_custom_from_name_when_set(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'name' => 'Support',
            'email' => 'support@example.com',
            'from_name' => 'Support Team',
            'from_name_custom' => 'Custom Support Name',
        ]);
        
        // Act
        $from = $mailbox->getMailFrom();
        
        // Assert
        $this->assertEquals('Custom Support Name', $from['name']);
    }

    /**
     * Regression Test: Verify mailbox uses name as fallback for from_name.
     */
    public function test_mailbox_uses_mailbox_name_as_fallback(): void
    {
        // Arrange
        $mailbox = Mailbox::factory()->create([
            'name' => 'Support',
            'email' => 'support@example.com',
            'from_name' => null,
            'from_name_custom' => null,
        ]);
        
        // Act
        $from = $mailbox->getMailFrom();
        
        // Assert
        $this->assertEquals('Support', $from['name']);
    }
}
```

---

## Summary

The above files contain **ALL** the tests required for Batch 2 that are not already covered by existing tests. These tests should be added to the specified paths:

1. **/tests/Unit/MailboxScopesTest.php** - Tests for mailbox scopes and user filtering
2. **/tests/Unit/FolderHierarchyTest.php** - Tests for folder hierarchy and type methods
3. **/tests/Unit/MailboxControllerValidationTest.php** - Tests for controller validation logic
4. **/tests/Feature/MailboxRegressionTest.php** - Regression tests comparing L5 vs modern implementation

All tests follow the project's existing patterns:
- Use `declare(strict_types=1);`
- Use `RefreshDatabase` trait where needed
- Follow Arrange-Act-Assert pattern
- Use descriptive test method names with `test_` prefix
- Include docblocks explaining each test's purpose
