<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ActivityLog;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\UnitTestCase;

class ConversationMethodsTest extends UnitTestCase
{
    protected User $user;
    protected Mailbox $mailbox;
    protected Folder $inboxFolder;
    protected Folder $trashFolder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->mailbox = Mailbox::factory()->create();
        $this->mailbox->users()->attach($this->user->id);

        // Use existing Inbox created by Observer
        $this->inboxFolder = $this->mailbox->folders()->where('type', Folder::TYPE_INBOX)->first();

        // Use existing Trash created by Observer
        $this->trashFolder = $this->mailbox->folders()->where('type', Folder::TYPE_TRASH)->first();
    }

    // ===== isUserFollowing tests =====

    public function test_is_user_following_returns_false_when_not_following(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();

        $this->assertFalse($conversation->isUserFollowing($this->user));
    }

    public function test_is_user_following_returns_true_when_following(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();
        $conversation->followers()->attach($this->user->id);

        $this->assertTrue($conversation->isUserFollowing($this->user));
    }

    public function test_is_user_following_returns_false_when_no_user_provided_and_not_authenticated(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();

        $this->assertFalse($conversation->isUserFollowing(null));
    }

    // ===== changeUser tests =====

    public function test_change_user_updates_user_id(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create(['user_id' => null]);
        $newUser = User::factory()->create();

        $result = $conversation->changeUser($newUser->id, $this->user);

        $this->assertTrue($result);
        $this->assertEquals($newUser->id, $conversation->user_id);
    }

    public function test_change_user_updates_user_updated_at(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create(['user_id' => null]);

        $conversation->changeUser($this->user->id, $this->user);

        $this->assertNotNull($conversation->user_updated_at);
    }

    public function test_change_user_can_unassign(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create(['user_id' => $this->user->id]);

        $result = $conversation->changeUser(null, $this->user);

        $this->assertTrue($result);
        $this->assertNull($conversation->user_id);
    }

    // ===== changeStatus tests =====

    public function test_change_status_updates_status(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create(['status' => Conversation::STATUS_ACTIVE]);

        $result = $conversation->changeStatus(Conversation::STATUS_CLOSED, $this->user);

        $this->assertTrue($result);
        $this->assertEquals(Conversation::STATUS_CLOSED, $conversation->status);
    }

    public function test_change_status_sets_closed_at_when_closing(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create([
            'status' => Conversation::STATUS_ACTIVE,
            'closed_at' => null,
        ]);

        $conversation->changeStatus(Conversation::STATUS_CLOSED, $this->user);

        $this->assertNotNull($conversation->closed_at);
        $this->assertEquals($this->user->id, $conversation->closed_by_user_id);
    }

    public function test_change_status_does_not_update_closed_at_when_already_closed(): void
    {
        $originalClosedAt = now()->subDay();
        $conversation = Conversation::factory()->for($this->mailbox)->create([
            'status' => Conversation::STATUS_CLOSED,
            'closed_at' => $originalClosedAt,
        ]);

        $conversation->changeStatus(Conversation::STATUS_CLOSED, $this->user);

        $this->assertEquals($originalClosedAt->format('Y-m-d H:i'), $conversation->closed_at->format('Y-m-d H:i'));
    }

    // ===== deleteToFolder tests =====

    public function test_delete_to_folder_moves_to_deleted_folder(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create([
            'folder_id' => $this->inboxFolder->id,
            'state' => Conversation::STATE_PUBLISHED,
        ]);

        $result = $conversation->deleteToFolder($this->user);

        $this->assertTrue($result);
        $this->assertEquals(Conversation::STATE_DELETED, $conversation->state);
        $this->assertEquals($this->trashFolder->id, $conversation->folder_id);
    }

    public function test_delete_to_folder_returns_false_when_no_deleted_folder(): void
    {
        $mailboxWithoutDeleted = Mailbox::factory()->create();
        // Delete default folders created by Observer
        $mailboxWithoutDeleted->folders()->delete();
        
        // Create only Inbox
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailboxWithoutDeleted->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $conversation = Conversation::factory()->for($mailboxWithoutDeleted)->create([
            'folder_id' => $folder->id,
        ]);

        $result = $conversation->deleteToFolder($this->user);

        $this->assertFalse($result);
    }

    // ===== restoreFromDeleted tests =====

    public function test_restore_from_deleted_moves_to_inbox(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create([
            'folder_id' => $this->trashFolder->id,
            'state' => Conversation::STATE_DELETED,
        ]);

        $result = $conversation->restoreFromDeleted($this->user);

        $this->assertTrue($result);
        $this->assertEquals(Conversation::STATE_PUBLISHED, $conversation->state);
        $this->assertEquals($this->inboxFolder->id, $conversation->folder_id);
    }

    // ===== moveToMailbox tests =====

    public function test_move_to_mailbox_changes_mailbox(): void
    {
        $newMailbox = Mailbox::factory()->create();
        $newInbox = $newMailbox->folders()->where('type', Folder::TYPE_INBOX)->first();

        $conversation = Conversation::factory()->for($this->mailbox)->create([
            'folder_id' => $this->inboxFolder->id,
        ]);

        $result = $conversation->moveToMailbox($newMailbox->id, $this->user);

        $this->assertTrue($result);
        $this->assertEquals($newMailbox->id, $conversation->mailbox_id);
        $this->assertEquals($newInbox->id, $conversation->folder_id);
    }

    public function test_move_to_mailbox_returns_false_when_no_inbox_in_target(): void
    {
        $newMailbox = Mailbox::factory()->create();
        // Delete default folders created by Observer
        $newMailbox->folders()->delete();

        $conversation = Conversation::factory()->for($this->mailbox)->create();

        $result = $conversation->moveToMailbox($newMailbox->id, $this->user);

        $this->assertFalse($result);
    }

    // ===== getBccArray and getCcArray tests =====

    public function test_get_bcc_array_returns_array(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create([
            'bcc' => ['bcc1@example.com', 'bcc2@example.com'],
        ]);

        $bcc = $conversation->getBccArray();

        $this->assertIsArray($bcc);
        $this->assertCount(2, $bcc);
        $this->assertContains('bcc1@example.com', $bcc);
    }

    public function test_get_bcc_array_returns_empty_array_when_null(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create(['bcc' => null]);

        $bcc = $conversation->getBccArray();

        $this->assertIsArray($bcc);
        $this->assertEmpty($bcc);
    }

    public function test_get_cc_array_returns_array(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create([
            'cc' => ['cc1@example.com', 'cc2@example.com'],
        ]);

        $cc = $conversation->getCcArray();

        $this->assertIsArray($cc);
        $this->assertCount(2, $cc);
    }

    // ===== sanitizeEmails tests =====

    public function test_sanitize_emails_filters_invalid_emails(): void
    {
        $emails = ['valid@example.com', 'invalid-email', 'another@valid.org', ''];

        $result = Conversation::sanitizeEmails($emails);

        $this->assertCount(2, $result);
        $this->assertContains('valid@example.com', $result);
        $this->assertContains('another@valid.org', $result);
        $this->assertNotContains('invalid-email', $result);
    }

    public function test_sanitize_emails_trims_whitespace(): void
    {
        $emails = ['  valid@example.com  ', "\ttest@test.com\n"];

        $result = Conversation::sanitizeEmails($emails);

        $this->assertContains('valid@example.com', $result);
        $this->assertContains('test@test.com', $result);
    }

    public function test_sanitize_emails_returns_empty_for_empty_input(): void
    {
        $result = Conversation::sanitizeEmails([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ===== star/unstar tests =====

    public function test_star_adds_user_to_starred(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();

        $conversation->star($this->user);

        $this->assertTrue($conversation->isStarredBy($this->user));
    }

    public function test_star_does_not_duplicate(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();

        $conversation->star($this->user);
        $conversation->star($this->user);

        $this->assertEquals(1, $conversation->starredByUsers()->count());
    }

    public function test_unstar_removes_user_from_starred(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();
        $conversation->star($this->user);

        $conversation->unstar($this->user);

        $this->assertFalse($conversation->isStarredBy($this->user));
    }

    public function test_is_starred_by_returns_false_when_not_starred(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();

        $this->assertFalse($conversation->isStarredBy($this->user));
    }

    // ===== getStatusLabel and getTypeLabel tests =====

    public function test_get_status_label_returns_correct_labels(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();

        $conversation->status = Conversation::STATUS_ACTIVE;
        $this->assertEquals(__('Active'), $conversation->getStatusLabel());

        $conversation->status = Conversation::STATUS_PENDING;
        $this->assertEquals(__('Pending'), $conversation->getStatusLabel());

        $conversation->status = Conversation::STATUS_CLOSED;
        $this->assertEquals(__('Closed'), $conversation->getStatusLabel());

        $conversation->status = Conversation::STATUS_SPAM;
        $this->assertEquals(__('Spam'), $conversation->getStatusLabel());
    }

    public function test_get_status_label_returns_unknown_for_invalid_status(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();
        $conversation->status = 999;

        $this->assertEquals(__('Unknown'), $conversation->getStatusLabel());
    }

    public function test_get_type_label_returns_correct_labels(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();

        $conversation->type = Conversation::TYPE_EMAIL;
        $this->assertEquals(__('Email'), $conversation->getTypeLabel());

        $conversation->type = Conversation::TYPE_PHONE;
        $this->assertEquals(__('Phone'), $conversation->getTypeLabel());

        $conversation->type = Conversation::TYPE_CHAT;
        $this->assertEquals(__('Chat'), $conversation->getTypeLabel());
    }

    public function test_get_type_label_returns_unknown_for_invalid_type(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();
        $conversation->type = 999;

        $this->assertEquals(__('Unknown'), $conversation->getTypeLabel());
    }

    // ===== Viewer methods tests =====

    public function test_set_viewer_adds_viewer_to_cache(): void
    {
        Cache::flush();

        Conversation::setViewer(1, $this->user->id, false);

        $cache = Cache::get(Conversation::VIEWER_CACHE_KEY, []);
        $this->assertArrayHasKey(1, $cache);
        $this->assertArrayHasKey($this->user->id, $cache[1]);
        $this->assertFalse($cache[1][$this->user->id]['r']);
    }

    public function test_set_viewer_with_replying_flag(): void
    {
        Cache::flush();

        Conversation::setViewer(1, $this->user->id, true);

        $cache = Cache::get(Conversation::VIEWER_CACHE_KEY, []);
        $this->assertTrue($cache[1][$this->user->id]['r']);
    }

    public function test_remove_viewer_removes_from_cache(): void
    {
        Cache::flush();
        Conversation::setViewer(1, $this->user->id, false);

        Conversation::removeViewer(1, $this->user->id);

        $cache = Cache::get(Conversation::VIEWER_CACHE_KEY, []);
        $this->assertArrayNotHasKey(1, $cache);
    }

    public function test_remove_viewer_only_removes_specific_user(): void
    {
        Cache::flush();
        $user2 = User::factory()->create();
        Conversation::setViewer(1, $this->user->id, false);
        Conversation::setViewer(1, $user2->id, false);

        Conversation::removeViewer(1, $this->user->id);

        $cache = Cache::get(Conversation::VIEWER_CACHE_KEY, []);
        $this->assertArrayHasKey(1, $cache);
        $this->assertArrayHasKey($user2->id, $cache[1]);
        $this->assertArrayNotHasKey($this->user->id, $cache[1]);
    }

    public function test_cleanup_viewers_removes_stale_entries(): void
    {
        Cache::flush();
        $cache = [
            1 => [
                $this->user->id => ['r' => false, 't' => time() - Conversation::VIEWER_STALE_TIMEOUT - 10],
            ],
        ];
        Cache::put(Conversation::VIEWER_CACHE_KEY, $cache, 300);

        Conversation::cleanupViewers();

        $newCache = Cache::get(Conversation::VIEWER_CACHE_KEY, []);
        $this->assertEmpty($newCache);
    }

    public function test_cleanup_viewers_keeps_fresh_entries(): void
    {
        Cache::flush();
        $cache = [
            1 => [
                $this->user->id => ['r' => false, 't' => time()],
            ],
        ];
        Cache::put(Conversation::VIEWER_CACHE_KEY, $cache, 300);

        Conversation::cleanupViewers();

        $newCache = Cache::get(Conversation::VIEWER_CACHE_KEY, []);
        $this->assertArrayHasKey(1, $newCache);
    }

    public function test_get_viewers_info_returns_empty_for_no_viewers(): void
    {
        Cache::flush();
        $conversation = Conversation::factory()->for($this->mailbox)->create();

        $viewers = Conversation::getViewersInfo([$conversation]);

        $this->assertEmpty($viewers);
    }

    public function test_get_viewers_info_returns_viewer_data(): void
    {
        Cache::flush();
        $conversation = Conversation::factory()->for($this->mailbox)->create();
        Conversation::setViewer($conversation->id, $this->user->id, false);

        $viewers = Conversation::getViewersInfo([$conversation]);

        $this->assertArrayHasKey($conversation->id, $viewers);
        $this->assertEquals($this->user->id, $viewers[$conversation->id]['user_id']);
        $this->assertFalse($viewers[$conversation->id]['replying']);
    }

    public function test_get_viewers_info_prioritizes_replying_viewers(): void
    {
        Cache::flush();
        $conversation = Conversation::factory()->for($this->mailbox)->create();
        $user2 = User::factory()->create();

        Conversation::setViewer($conversation->id, $this->user->id, false);
        Conversation::setViewer($conversation->id, $user2->id, true);

        $viewers = Conversation::getViewersInfo([$conversation]);

        $this->assertEquals($user2->id, $viewers[$conversation->id]['user_id']);
        $this->assertTrue($viewers[$conversation->id]['replying']);
    }

    public function test_get_viewers_info_excludes_specified_users(): void
    {
        Cache::flush();
        $conversation = Conversation::factory()->for($this->mailbox)->create();
        Conversation::setViewer($conversation->id, $this->user->id, false);

        $viewers = Conversation::getViewersInfo([$conversation], ['id', 'first_name'], [$this->user->id]);

        $this->assertArrayNotHasKey($conversation->id, $viewers);
    }

    // ===== Constant tests =====

    public function test_viewer_cache_constants_exist(): void
    {
        $this->assertEquals('conv_view', Conversation::VIEWER_CACHE_KEY);
        $this->assertEquals(300, Conversation::VIEWER_CACHE_TTL);
        $this->assertEquals(120, Conversation::VIEWER_STALE_TIMEOUT);
    }

    public function test_user_unassigned_constant_exists(): void
    {
        $this->assertEquals('unassigned', Conversation::USER_UNASSIGNED);
    }

    public function test_search_filters_list_contains_expected_filters(): void
    {
        $expected = [
            'assigned', 'customer', 'mailbox', 'status', 'state',
            'subject', 'attachments', 'type', 'body', 'number',
            'following', 'id', 'after', 'before',
        ];

        foreach ($expected as $filter) {
            $this->assertContains($filter, Conversation::$search_filters);
        }
    }

    // ===== Search tests =====

    public function test_search_filters_by_user_mailbox_access(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->mailbox->users()->attach($user->id);

        $otherMailbox = Mailbox::factory()->create();
        $otherFolder = Folder::factory()->create([
            'mailbox_id' => $otherMailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        Conversation::factory()->for($this->mailbox)->create(['subject' => 'Test 1']);
        Conversation::factory()->for($otherMailbox)->create(['subject' => 'Test 2']);

        $results = Conversation::search('Test', [], $user)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Test 1', $results->first()->subject);
    }

    public function test_search_with_status_filter(): void
    {
        Conversation::factory()->for($this->mailbox)->create([
            'subject' => 'Active Conv',
            'status' => Conversation::STATUS_ACTIVE,
        ]);
        Conversation::factory()->for($this->mailbox)->create([
            'subject' => 'Closed Conv',
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $results = Conversation::search('', ['status' => Conversation::STATUS_ACTIVE], $this->user)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Active Conv', $results->first()->subject);
    }

    public function test_search_with_unassigned_filter(): void
    {
        Conversation::factory()->for($this->mailbox)->create([
            'subject' => 'Assigned',
            'user_id' => $this->user->id,
        ]);
        Conversation::factory()->for($this->mailbox)->create([
            'subject' => 'Unassigned',
            'user_id' => null,
        ]);

        $results = Conversation::search('', ['assigned' => Conversation::USER_UNASSIGNED], $this->user)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Unassigned', $results->first()->subject);
    }

    public function test_search_with_assigned_user_filter(): void
    {
        Conversation::factory()->for($this->mailbox)->create([
            'subject' => 'My Assignment',
            'user_id' => $this->user->id,
        ]);
        Conversation::factory()->for($this->mailbox)->create([
            'subject' => 'Unassigned',
            'user_id' => null,
        ]);

        $results = Conversation::search('', ['assigned' => $this->user->id], $this->user)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('My Assignment', $results->first()->subject);
    }

    public function test_search_with_type_filter(): void
    {
        Conversation::factory()->for($this->mailbox)->create([
            'subject' => 'Email Conv',
            'type' => Conversation::TYPE_EMAIL,
        ]);
        Conversation::factory()->for($this->mailbox)->create([
            'subject' => 'Phone Conv',
            'type' => Conversation::TYPE_PHONE,
        ]);

        $results = Conversation::search('', ['type' => Conversation::TYPE_PHONE], $this->user)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Phone Conv', $results->first()->subject);
    }

    public function test_search_with_attachments_filter(): void
    {
        Conversation::factory()->for($this->mailbox)->create([
            'subject' => 'With Attachments',
            'has_attachments' => true,
        ]);
        Conversation::factory()->for($this->mailbox)->create([
            'subject' => 'Without Attachments',
            'has_attachments' => false,
        ]);

        $results = Conversation::search('', ['attachments' => true], $this->user)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('With Attachments', $results->first()->subject);
    }

    public function test_search_with_date_range_filter(): void
    {
        Conversation::factory()->for($this->mailbox)->create([
            'subject' => 'Old Conv',
            'created_at' => now()->subDays(30),
        ]);
        Conversation::factory()->for($this->mailbox)->create([
            'subject' => 'New Conv',
            'created_at' => now(),
        ]);

        $results = Conversation::search('', [
            'after' => now()->subDays(7)->format('Y-m-d'),
        ], $this->user)->get();

        $this->assertCount(1, $results);
        $this->assertEquals('New Conv', $results->first()->subject);
    }

    public function test_search_by_conversation_number(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create([
            'number' => 12345,
        ]);

        $results = Conversation::search('12345', [], $this->user)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($conversation->id, $results->first()->id);
    }
}
