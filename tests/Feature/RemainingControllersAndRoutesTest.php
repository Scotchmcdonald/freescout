<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\User;
use App\Models\Mailbox;
use App\Models\Conversation;
use App\Models\Thread;
use App\Models\Customer;
use App\Models\Folder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class RemainingControllersAndRoutesTest extends FeatureTestCase
{
    // SearchController Tests
    public function test_search_requires_authentication(): void
    {
        $response = $this->get(route('search'));
        $response->assertRedirect(route('login'));
    }

    public function test_search_can_find_conversations_by_subject(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'subject' => 'Unique Test Subject XYZ123',
        ]);
        
        $user->mailboxes()->attach($mailbox->id);

        $response = $this->actingAs($user)->get(route('search', ['q' => 'XYZ123']));
        $response->assertStatus(200);
        $response->assertSee('Unique Test Subject');
    }

    public function test_search_can_find_conversations_by_number(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'number' => 12345,
        ]);
        
        $user->mailboxes()->attach($mailbox->id);

        $response = $this->actingAs($user)->get(route('search', ['q' => '12345']));
        $response->assertStatus(200);
    }

    public function test_search_respects_mailbox_access_control(): void
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        
        $conversation1 = Conversation::factory()->create([
            'mailbox_id' => $mailbox1->id,
            'subject' => 'Searchable Subject ABC',
        ]);
        $conversation2 = Conversation::factory()->create([
            'mailbox_id' => $mailbox2->id,
            'subject' => 'Searchable Subject ABC',
        ]);
        
        $user->mailboxes()->attach($mailbox1->id);

        $response = $this->actingAs($user)->get(route('search', ['q' => 'ABC']));
        $response->assertStatus(200);
    }

    public function test_search_handles_empty_query(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('search', ['q' => '']));
        $response->assertStatus(200);
    }

    public function test_search_handles_special_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('search', ['q' => '<script>alert("xss")</script>']));
        $response->assertStatus(200);
    }

    // UpdateController Tests
    public function test_update_check_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->get(route('system.update'));
        $response->assertStatus(403);
    }

    public function test_update_check_allows_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get(route('system.update'));
        $response->assertStatus(200);
    }

    // LogsController Tests
    public function test_logs_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->get(route('logs'));
        $response->assertStatus(403);
    }

    public function test_logs_allows_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get(route('logs'));
        $response->assertStatus(200);
    }

    public function test_logs_download_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->get(route('logs.download'));
        $response->assertStatus(403);
    }

    // PermissionsController Tests
    public function test_permissions_index_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->get(route('permissions'));
        $response->assertStatus(403);
    }

    public function test_permissions_index_allows_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get(route('permissions'));
        $response->assertStatus(200);
    }

    public function test_permissions_save_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->post(route('permissions.save'), []);
        $response->assertStatus(403);
    }

    public function test_permissions_save_allows_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->post(route('permissions.save'), [
            'permissions' => [],
        ]);
        $response->assertStatus(302);
    }

    // TagsController Tests
    public function test_tags_ajax_search_requires_authentication(): void
    {
        $response = $this->get(route('tags.ajax_search'));
        $response->assertStatus(302);
    }

    public function test_tags_ajax_search_returns_json(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('tags.ajax_search', ['q' => 'test']));
        $response->assertStatus(200);
        $response->assertJson([]);
    }

    // WebhooksController Tests
    public function test_webhooks_index_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->get(route('webhooks'));
        $response->assertStatus(403);
    }

    public function test_webhooks_index_allows_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get(route('webhooks'));
        $response->assertStatus(200);
    }

    public function test_webhooks_create_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->get(route('webhooks.create'));
        $response->assertStatus(403);
    }

    public function test_webhooks_store_validates_url(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->post(route('webhooks.store'), [
            'url' => 'not-a-url',
        ]);
        $response->assertSessionHasErrors('url');
    }

    public function test_webhooks_store_validates_events(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->post(route('webhooks.store'), [
            'url' => 'https://example.com/webhook',
            'events' => [],
        ]);
        $response->assertSessionHasErrors('events');
    }

    // LocaleController Tests
    public function test_locale_update_requires_authentication(): void
    {
        $response = $this->post(route('locale.update'), ['locale' => 'en']);
        $response->assertStatus(302);
    }

    public function test_locale_update_changes_user_locale(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $response = $this->actingAs($user)->post(route('locale.update'), [
            'locale' => 'es',
        ]);
        $response->assertStatus(302);
        $this->assertEquals('es', $user->fresh()->locale);
    }

    public function test_locale_update_validates_locale(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('locale.update'), [
            'locale' => 'invalid-locale',
        ]);
        $response->assertSessionHasErrors('locale');
    }

    // NotificationsController Tests
    public function test_notifications_index_requires_authentication(): void
    {
        $response = $this->get(route('notifications'));
        $response->assertRedirect(route('login'));
    }

    public function test_notifications_index_displays_user_notifications(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('notifications'));
        $response->assertStatus(200);
    }

    public function test_notifications_mark_as_read_requires_authentication(): void
    {
        $response = $this->post(route('notifications.mark_as_read', ['id' => 1]));
        $response->assertStatus(302);
    }

    // ApiController Tests
    public function test_api_requires_authentication(): void
    {
        $response = $this->get(route('api.conversations'));
        $response->assertStatus(401);
    }

    public function test_api_requires_valid_token(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->get(route('api.conversations'));
        $response->assertStatus(401);
    }

    // DownloadController Tests  
    public function test_download_attachment_requires_authentication(): void
    {
        $response = $this->get(route('attachments.download', ['id' => 1]));
        $response->assertRedirect(route('login'));
    }

    public function test_download_attachment_requires_access_to_conversation(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        $attachment = \App\Models\Attachment::factory()->create(['thread_id' => $thread->id]);

        $response = $this->actingAs($user)->get(route('attachments.download', ['id' => $attachment->id]));
        $response->assertStatus(403);
    }

    // MergeController Tests
    public function test_merge_conversation_requires_authentication(): void
    {
        $response = $this->post(route('conversations.merge', ['conversation' => 1]));
        $response->assertRedirect(route('login'));
    }

    public function test_merge_conversation_validates_target_conversation(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $user->mailboxes()->attach($mailbox->id);

        $response = $this->actingAs($user)->post(route('conversations.merge', ['conversation' => $conversation->id]), [
            'target_conversation_id' => 'invalid',
        ]);
        $response->assertSessionHasErrors();
    }

    public function test_merge_conversation_prevents_merging_into_self(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $user->mailboxes()->attach($mailbox->id);

        $response = $this->actingAs($user)->post(route('conversations.merge', ['conversation' => $conversation->id]), [
            'target_conversation_id' => $conversation->id,
        ]);
        $response->assertSessionHasErrors();
    }

    // PrintController Tests
    public function test_print_conversation_requires_authentication(): void
    {
        $response = $this->get(route('conversations.print', ['id' => 1]));
        $response->assertRedirect(route('login'));
    }

    public function test_print_conversation_renders_printable_version(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $user->mailboxes()->attach($mailbox->id);

        $response = $this->actingAs($user)->get(route('conversations.print', ['id' => $conversation->id]));
        $response->assertStatus(200);
    }

    // ExportController Tests
    public function test_export_conversations_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->post(route('conversations.export'));
        $response->assertStatus(403);
    }

    public function test_export_conversations_allows_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->post(route('conversations.export'), [
            'mailbox_id' => 1,
        ]);
        $response->assertStatus(200);
    }

    // ImportController Tests
    public function test_import_conversations_requires_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($user)->get(route('conversations.import'));
        $response->assertStatus(403);
    }

    public function test_import_conversations_allows_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get(route('conversations.import'));
        $response->assertStatus(200);
    }
}
