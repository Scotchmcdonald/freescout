<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\ModulesController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use App\Services\SmtpService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Nwidart\Modules\Facades\Module;
use Tests\IntegrationTestCase;

class ControllerCoverageTest extends IntegrationTestCase
{

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->user = User::factory()->create(['role' => User::ROLE_USER]);
    }

    // ========================================
    // ConversationController Tests (7 tests)
    // ========================================

    public function test_clone_creates_duplicate_conversation(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->admin->mailboxes()->attach($mailbox->id);
        
        $customer = Customer::factory()->create();
        $originalConversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
            'subject' => 'Original Subject',
            'status' => 1,
            'state' => 2,
        ]);
        
        $thread = Thread::factory()->create([
            'conversation_id' => $originalConversation->id,
            'customer_id' => $customer->id,
            'body' => 'Original thread body',
            'state' => 2,
        ]);

        $request = Request::create('/conversations/clone', 'POST');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ConversationController;
        $response = $controller->clone($request, $mailbox, $thread);

        // Verify redirect response
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        
        // Verify new conversation was created
        $this->assertDatabaseCount('conversations', 2);
        
        // Verify the cloned conversation has same subject
        $clonedConversation = Conversation::where('id', '!=', $originalConversation->id)->first();
        $this->assertNotNull($clonedConversation);
        $this->assertEquals('Original Subject', $clonedConversation->subject);
    }

    public function test_move_transfers_conversation_to_different_mailbox(): void
    {
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        
        $this->admin->mailboxes()->attach([$mailbox1->id, $mailbox2->id]);
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox1->id,
        ]);

        $request = Request::create('/conversations/move', 'POST', [
            'mailbox_id' => $mailbox2->id,
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ConversationController;
        $response = $controller->move($request, $conversation);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        
        $conversation->refresh();
        $this->assertEquals($mailbox2->id, $conversation->mailbox_id);
    }

    public function test_update_thread_modifies_thread_content(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->admin->mailboxes()->attach($mailbox->id);
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => 'Original body',
        ]);

        $request = Request::create('/conversations/threads/update', 'POST', [
            'body' => 'Updated thread body',
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ConversationController;
        $response = $controller->updateThread($request, $conversation, $thread);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        
        $thread->refresh();
        $this->assertEquals('Updated thread body', $thread->body);
        $this->assertEquals($this->admin->id, $thread->edited_by_user_id);
        $this->assertNotNull($thread->edited_at);
    }

    public function test_update_settings_changes_conversation_config(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->admin->mailboxes()->attach($mailbox->id);
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'meta' => [],
        ]);

        $request = Request::create('/conversations/settings', 'POST', [
            'tags' => 'urgent, important, followup',
            'priority' => 'high',
            'custom_field_1' => 'Value 1',
            'custom_field_2' => 'Value 2',
            'internal_notes' => 'Internal note content',
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ConversationController;
        $response = $controller->updateSettings($request, $conversation);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        
        $conversation->refresh();
        $this->assertIsArray($conversation->meta);
        $this->assertEquals(['urgent', 'important', 'followup'], $conversation->meta['tags']);
        $this->assertEquals('high', $conversation->meta['priority']);
        $this->assertEquals('Value 1', $conversation->meta['custom_field_1']);
        $this->assertEquals('Value 2', $conversation->meta['custom_field_2']);
        $this->assertEquals('Internal note content', $conversation->meta['internal_notes']);
    }

    public function test_chats_returns_chat_conversations_list(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->user->mailboxes()->attach($mailbox->id);
        
        // Create chat-type conversation
        $chatConversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1, // Chat type
        ]);
        
        // Create non-chat conversation
        $emailConversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 2, // Email type
        ]);

        $request = Request::create('/conversations/chats', 'GET');
        $request->setUserResolver(fn () => $this->user);

        $controller = new ConversationController;
        $view = $controller->chats($request);

        $this->assertEquals('conversations.chats', $view->name());
        
        $conversations = $view->getData()['conversations'];
        // Should only contain chat-type conversations
        $this->assertTrue($conversations->contains('id', $chatConversation->id));
        $this->assertFalse($conversations->contains('id', $emailConversation->id));
    }

    public function test_upload_handles_file_attachment(): void
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $request = Request::create('/conversations/upload', 'POST');
        $request->files->set('file', $file);

        $controller = new ConversationController;
        $response = $controller->upload($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('document.pdf', $data['filename']);
        $this->assertArrayHasKey('path', $data);
        $this->assertArrayHasKey('size', $data);
        
        // Verify file was stored
        Storage::disk('public')->assertExists($data['path']);
    }

    public function test_destroy_soft_deletes_conversation(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->admin->mailboxes()->attach($mailbox->id);
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $conversationId = $conversation->id;

        $request = Request::create('/conversations/'.$conversationId, 'DELETE');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ConversationController;
        $response = $controller->destroy($request, $conversation);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        
        // Verify conversation is soft-deleted
        $this->assertSoftDeleted('conversations', ['id' => $conversationId]);
    }

    // ========================================
    // ConversationController Edge Cases & Error Tests
    // ========================================

    public function test_clone_fails_for_unauthorized_user(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $request = Request::create('/conversations/clone', 'POST');
        $request->setUserResolver(fn () => $this->user); // Not attached to mailbox

        $controller = new ConversationController;
        
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $controller->clone($request, $mailbox, $thread);
    }

    public function test_clone_preserves_all_thread_properties(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->admin->mailboxes()->attach($mailbox->id);
        
        $customer = Customer::factory()->create();
        $originalConversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
        ]);
        
        $thread = Thread::factory()->create([
            'conversation_id' => $originalConversation->id,
            'customer_id' => $customer->id,
            'body' => 'Test body',
            'cc' => json_encode(['cc@example.com']),
            'bcc' => json_encode(['bcc@example.com']),
            'headers' => json_encode(['X-Custom' => 'value']),
            'has_attachments' => false,
        ]);

        $request = Request::create('/conversations/clone', 'POST');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ConversationController;
        $controller->clone($request, $mailbox, $thread);

        $clonedConversation = Conversation::where('id', '!=', $originalConversation->id)->first();
        $clonedThread = Thread::where('conversation_id', $clonedConversation->id)->first();
        
        $this->assertEquals($thread->body, $clonedThread->body);
        $this->assertEquals($thread->cc, $clonedThread->cc);
        $this->assertEquals($thread->bcc, $clonedThread->bcc);
        $this->assertEquals($thread->headers, $clonedThread->headers);
    }

    public function test_move_fails_when_user_lacks_access_to_target_mailbox(): void
    {
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        
        $this->user->mailboxes()->attach($mailbox1->id);
        // User does NOT have access to mailbox2
        
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox1->id]);

        $request = Request::create('/conversations/move', 'POST', [
            'mailbox_id' => $mailbox2->id,
        ]);
        $request->setUserResolver(fn () => $this->user);

        $controller = new ConversationController;
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->move($request, $conversation);
    }

    public function test_move_returns_json_when_expected(): void
    {
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        
        $this->admin->mailboxes()->attach([$mailbox1->id, $mailbox2->id]);
        
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox1->id]);

        $request = Request::create('/conversations/move', 'POST', [
            'mailbox_id' => $mailbox2->id,
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ConversationController;
        $response = $controller->move($request, $conversation);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function test_move_validates_mailbox_id_required(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->admin->mailboxes()->attach($mailbox->id);
        
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $request = Request::create('/conversations/move', 'POST', []);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ConversationController;
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->move($request, $conversation);
    }

    public function test_update_thread_fails_when_thread_not_in_conversation(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->admin->mailboxes()->attach($mailbox->id);
        
        $conversation1 = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $conversation2 = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $thread = Thread::factory()->create(['conversation_id' => $conversation2->id]);

        $request = Request::create('/conversations/threads/update', 'POST', [
            'body' => 'Updated body',
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ConversationController;
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->updateThread($request, $conversation1, $thread);
    }

    public function test_update_thread_returns_json_when_expected(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->admin->mailboxes()->attach($mailbox->id);
        
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $request = Request::create('/conversations/threads/update', 'POST', [
            'body' => 'Updated body',
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ConversationController;
        $response = $controller->updateThread($request, $conversation, $thread);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function test_update_thread_validates_body_required(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->admin->mailboxes()->attach($mailbox->id);
        
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);

        $request = Request::create('/conversations/threads/update', 'POST', []);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ConversationController;
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->updateThread($request, $conversation, $thread);
    }

    public function test_update_settings_handles_empty_tags(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->admin->mailboxes()->attach($mailbox->id);
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'meta' => [],
        ]);

        $request = Request::create('/conversations/settings', 'POST', [
            'tags' => '',
            'priority' => 'normal',
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ConversationController;
        $response = $controller->updateSettings($request, $conversation);

        $conversation->refresh();
        $this->assertEmpty($conversation->meta['tags']);
        $this->assertEquals('normal', $conversation->meta['priority']);
    }

    public function test_update_settings_returns_json_when_expected(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->admin->mailboxes()->attach($mailbox->id);
        
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $request = Request::create('/conversations/settings', 'POST', [
            'priority' => 'urgent',
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ConversationController;
        $response = $controller->updateSettings($request, $conversation);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function test_update_settings_validates_priority_values(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->admin->mailboxes()->attach($mailbox->id);
        
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $request = Request::create('/conversations/settings', 'POST', [
            'priority' => 'invalid_priority',
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ConversationController;
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->updateSettings($request, $conversation);
    }

    public function test_chats_only_shows_user_accessible_mailboxes(): void
    {
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        
        $this->user->mailboxes()->attach($mailbox1->id);
        // User does NOT have access to mailbox2
        
        $chatInAccessible = Conversation::factory()->create([
            'mailbox_id' => $mailbox1->id,
            'type' => 1,
        ]);
        
        $chatNotAccessible = Conversation::factory()->create([
            'mailbox_id' => $mailbox2->id,
            'type' => 1,
        ]);

        $request = Request::create('/conversations/chats', 'GET');
        $request->setUserResolver(fn () => $this->user);

        $controller = new ConversationController;
        $view = $controller->chats($request);

        $conversations = $view->getData()['conversations'];
        $this->assertTrue($conversations->contains('id', $chatInAccessible->id));
        $this->assertFalse($conversations->contains('id', $chatNotAccessible->id));
    }

    public function test_chats_loads_active_conversation_when_id_provided(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->user->mailboxes()->attach($mailbox->id);
        
        $chat = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => 1,
        ]);

        $request = Request::create('/conversations/chats?id='.$chat->id, 'GET');
        $request->setUserResolver(fn () => $this->user);

        $controller = new ConversationController;
        $view = $controller->chats($request);

        $activeConversation = $view->getData()['activeConversation'];
        $this->assertNotNull($activeConversation);
        $this->assertEquals($chat->id, $activeConversation->id);
    }

    public function test_upload_validates_file_required(): void
    {
        $request = Request::create('/conversations/upload', 'POST', []);

        $controller = new ConversationController;
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->upload($request);
    }

    public function test_upload_validates_file_size_limit(): void
    {
        Storage::fake('public');
        
        // Try to upload file larger than 10MB
        $file = UploadedFile::fake()->create('large.pdf', 11000); // 11MB

        $request = Request::create('/conversations/upload', 'POST');
        $request->files->set('file', $file);

        $controller = new ConversationController;
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->upload($request);
    }

    public function test_upload_handles_various_file_types(): void
    {
        Storage::fake('public');
        
        $files = [
            UploadedFile::fake()->image('photo.jpg'),
            UploadedFile::fake()->create('document.docx', 100),
            UploadedFile::fake()->create('spreadsheet.xlsx', 100),
        ];

        foreach ($files as $file) {
            $request = Request::create('/conversations/upload', 'POST');
            $request->files->set('file', $file);

            $controller = new ConversationController;
            $response = $controller->upload($request);

            $this->assertEquals(200, $response->getStatusCode());
            
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($data['success']);
        }
    }

    public function test_destroy_fails_for_unauthorized_user(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $request = Request::create('/conversations/'.$conversation->id, 'DELETE');
        $request->setUserResolver(fn () => $this->user); // Not attached to mailbox

        $controller = new ConversationController;
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->destroy($request, $conversation);
    }

    public function test_destroy_only_soft_deletes(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->admin->mailboxes()->attach($mailbox->id);
        
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $conversationId = $conversation->id;

        $request = Request::create('/conversations/'.$conversationId, 'DELETE');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ConversationController;
        $controller->destroy($request, $conversation);

        // Verify conversation still exists in database but is soft-deleted
        $this->assertDatabaseHas('conversations', ['id' => $conversationId]);
        $this->assertSoftDeleted('conversations', ['id' => $conversationId]);
    }

    // ========================================
    // SettingsController Tests (Expanded)
    // ========================================

    public function test_validate_smtp_checks_email_configuration(): void
    {
        $smtpService = $this->createMock(SmtpService::class);
        $smtpService->expects($this->once())
            ->method('validateSettings')
            ->with([
                'mail_host' => 'smtp.example.com',
                'mail_port' => '587',
                'mail_username' => 'user@example.com',
                'mail_password' => 'password',
            ])
            ->willReturn([]);

        $request = Request::create('/settings/validate-smtp', 'POST', [
            'mail_host' => 'smtp.example.com',
            'mail_port' => '587',
            'mail_username' => 'user@example.com',
            'mail_password' => 'password',
        ]);

        $controller = new SettingsController;
        $response = $controller->validateSmtp($request, $smtpService);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('SMTP settings are valid.', $data['message']);
    }

    public function test_validate_smtp_returns_errors_for_invalid_settings(): void
    {
        $smtpService = $this->createMock(SmtpService::class);
        $smtpService->expects($this->once())
            ->method('validateSettings')
            ->willReturn([
                'mail_host' => 'Host is required',
                'mail_port' => 'Invalid port number',
            ]);

        $request = Request::create('/settings/validate-smtp', 'POST', [
            'mail_host' => '',
            'mail_port' => 'invalid',
        ]);

        $controller = new SettingsController;
        $response = $controller->validateSmtp($request, $smtpService);

        $this->assertEquals(422, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('errors', $data);
    }

    public function test_alerts_displays_alert_settings_page(): void
    {
        $request = Request::create('/settings/alerts', 'GET');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new SettingsController;
        $view = $controller->alerts();

        $this->assertEquals('settings.alerts', $view->name());
        $this->assertArrayHasKey('settings', $view->getData());
    }

    public function test_update_alerts_saves_alert_configuration(): void
    {
        $request = Request::create('/settings/alerts', 'POST', [
            'alerts' => [
                'system_errors' => true,
                'high_queue' => true,
                'failed_jobs' => false,
                'disk_space' => true,
                'db_connection' => false,
            ],
            'queue_threshold' => 100,
            'alert_recipients' => 'admin@example.com',
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new SettingsController;
        $response = $controller->updateAlerts($request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        
        // Verify settings were saved
        $this->assertDatabaseHas('options', [
            'name' => 'alert_system_errors',
            'value' => '1',
        ]);
        
        $this->assertDatabaseHas('options', [
            'name' => 'alert_failed_jobs',
            'value' => '0',
        ]);
    }

    public function test_update_alerts_validates_queue_threshold_minimum(): void
    {
        $request = Request::create('/settings/alerts', 'POST', [
            'queue_threshold' => 5, // Less than minimum of 10
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new SettingsController;
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->updateAlerts($request);
    }

    public function test_update_alerts_validates_queue_threshold_maximum(): void
    {
        $request = Request::create('/settings/alerts', 'POST', [
            'queue_threshold' => 15000, // More than maximum of 10000
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new SettingsController;
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->updateAlerts($request);
    }

    public function test_update_alerts_handles_missing_alerts_array(): void
    {
        $request = Request::create('/settings/alerts', 'POST', [
            'queue_threshold' => 100,
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new SettingsController;
        $response = $controller->updateAlerts($request);

        // Should not throw exception, should handle gracefully
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    // ========================================
    // UserController Tests (Expanded)
    // ========================================

    public function test_ajax_handles_user_search_operation(): void
    {
        $user1 = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'status' => 1,
        ]);
        
        $user2 = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'status' => 1,
        ]);

        $request = Request::create('/users/ajax', 'POST', [
            'action' => 'search',
            'query' => 'John',
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new UserController;
        $response = $controller->ajax($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('users', $data);
        $this->assertGreaterThan(0, count($data['users']));
        
        // Verify John is in results
        $foundJohn = false;
        foreach ($data['users'] as $user) {
            if (str_contains($user['name'], 'John')) {
                $foundJohn = true;
                break;
            }
        }
        $this->assertTrue($foundJohn);
    }

    public function test_ajax_search_only_returns_active_users(): void
    {
        $activeUser = User::factory()->create([
            'first_name' => 'Active',
            'last_name' => 'User',
            'status' => 1,
        ]);
        
        $inactiveUser = User::factory()->create([
            'first_name' => 'Inactive',
            'last_name' => 'User',
            'status' => 2,
        ]);

        $request = Request::create('/users/ajax', 'POST', [
            'action' => 'search',
            'query' => 'User',
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new UserController;
        $response = $controller->ajax($request);

        $data = json_decode($response->getContent(), true);
        
        $foundActive = false;
        $foundInactive = false;
        foreach ($data['users'] as $user) {
            if (str_contains($user['name'], 'Active')) {
                $foundActive = true;
            }
            if (str_contains($user['name'], 'Inactive')) {
                $foundInactive = true;
            }
        }
        
        $this->assertTrue($foundActive);
        $this->assertFalse($foundInactive);
    }

    public function test_ajax_search_limits_results_to_25(): void
    {
        // Create 30 active users
        User::factory()->count(30)->create(['status' => 1]);

        $request = Request::create('/users/ajax', 'POST', [
            'action' => 'search',
            'query' => '',
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new UserController;
        $response = $controller->ajax($request);

        $data = json_decode($response->getContent(), true);
        $this->assertLessThanOrEqual(25, count($data['users']));
    }

    public function test_ajax_handles_toggle_status_action(): void
    {
        $targetUser = User::factory()->create(['status' => 1]);

        $request = Request::create('/users/ajax', 'POST', [
            'action' => 'toggle_status',
            'user_id' => $targetUser->id,
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new UserController;
        $response = $controller->ajax($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(2, $data['status']);
        
        $targetUser->refresh();
        $this->assertEquals(2, $targetUser->status);
    }

    public function test_ajax_toggle_status_toggles_back(): void
    {
        $targetUser = User::factory()->create(['status' => 2]);

        $request = Request::create('/users/ajax', 'POST', [
            'action' => 'toggle_status',
            'user_id' => $targetUser->id,
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new UserController;
        $response = $controller->ajax($request);

        $data = json_decode($response->getContent(), true);
        $this->assertEquals(1, $data['status']);
        
        $targetUser->refresh();
        $this->assertEquals(1, $targetUser->status);
    }

    public function test_ajax_returns_error_for_invalid_action(): void
    {
        $request = Request::create('/users/ajax', 'POST', [
            'action' => 'invalid_action',
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new UserController;
        $response = $controller->ajax($request);

        $this->assertEquals(400, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Invalid action', $data['message']);
    }

    public function test_ajax_search_handles_empty_query(): void
    {
        User::factory()->count(5)->create(['status' => 1]);

        $request = Request::create('/users/ajax', 'POST', [
            'action' => 'search',
            'query' => '',
        ]);
        $request->setUserResolver(fn () => $this->admin);

        $controller = new UserController;
        $response = $controller->ajax($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('users', $data);
    }

    public function test_user_setup_displays_initial_setup_page(): void
    {
        $inviteUser = User::factory()->create([
            'invite_hash' => 'test-hash-123',
            'status' => 1,
        ]);

        $controller = new UserController;
        $view = $controller->userSetup('test-hash-123');

        $this->assertEquals('users.setup', $view->name());
        $this->assertArrayHasKey('user', $view->getData());
        $this->assertEquals($inviteUser->id, $view->getData()['user']->id);
    }

    public function test_user_setup_returns_404_for_invalid_hash(): void
    {
        $controller = new UserController;
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->userSetup('invalid-hash-xyz');
    }

    public function test_user_setup_redirects_authenticated_users(): void
    {
        $inviteUser = User::factory()->create([
            'invite_hash' => 'test-hash-789',
        ]);

        // Simulate authenticated user
        $this->actingAs($this->admin);

        $controller = new UserController;
        $response = $controller->userSetup('test-hash-789');

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    public function test_user_setup_save_processes_setup_form(): void
    {
        $inviteUser = User::factory()->create([
            'invite_hash' => 'test-hash-456',
            'email' => 'original@example.com',
            'password' => '',
            'status' => 1,
        ]);

        $request = Request::create('/users/setup/test-hash-456', 'POST', [
            'email' => 'updated@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'timezone' => 'America/New_York',
            'time_format' => '12',
            'job_title' => 'Support Agent',
            'phone' => '+1234567890',
        ]);

        $controller = new UserController;
        $response = $controller->userSetupSave('test-hash-456', $request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        
        $inviteUser->refresh();
        $this->assertEquals('updated@example.com', $inviteUser->email);
        $this->assertEquals('America/New_York', $inviteUser->timezone);
        $this->assertEquals('Support Agent', $inviteUser->job_title);
        $this->assertEquals('+1234567890', $inviteUser->phone);
        $this->assertNotEmpty($inviteUser->password);
    }

    public function test_user_setup_save_validates_email_required(): void
    {
        $inviteUser = User::factory()->create([
            'invite_hash' => 'test-hash-valid',
        ]);

        $request = Request::create('/users/setup/test-hash-valid', 'POST', [
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'timezone' => 'America/New_York',
            'time_format' => '12',
        ]);

        $controller = new UserController;
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->userSetupSave('test-hash-valid', $request);
    }

    public function test_user_setup_save_validates_password_confirmation(): void
    {
        $inviteUser = User::factory()->create([
            'invite_hash' => 'test-hash-mismatch',
        ]);

        $request = Request::create('/users/setup/test-hash-mismatch', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
            'timezone' => 'America/New_York',
            'time_format' => '12',
        ]);

        $controller = new UserController;
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->userSetupSave('test-hash-mismatch', $request);
    }

    public function test_user_setup_save_validates_password_minimum_length(): void
    {
        $inviteUser = User::factory()->create([
            'invite_hash' => 'test-hash-short',
        ]);

        $request = Request::create('/users/setup/test-hash-short', 'POST', [
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'timezone' => 'America/New_York',
            'time_format' => '12',
        ]);

        $controller = new UserController;
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->userSetupSave('test-hash-short', $request);
    }

    public function test_user_setup_save_validates_time_format(): void
    {
        $inviteUser = User::factory()->create([
            'invite_hash' => 'test-hash-format',
        ]);

        $request = Request::create('/users/setup/test-hash-format', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'timezone' => 'America/New_York',
            'time_format' => 'invalid',
        ]);

        $controller = new UserController;
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $controller->userSetupSave('test-hash-format', $request);
    }

    public function test_user_setup_save_returns_404_for_invalid_hash(): void
    {
        $request = Request::create('/users/setup/invalid-hash', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'timezone' => 'America/New_York',
            'time_format' => '12',
        ]);

        $controller = new UserController;
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->userSetupSave('invalid-hash', $request);
    }

    // ========================================
    // ModulesController Tests (Expanded)
    // ========================================

    public function test_enable_activates_module(): void
    {
        // Mock Module facade
        $mockModule = $this->createMock(\Nwidart\Modules\Module::class);
        $mockModule->expects($this->once())
            ->method('enable');
        $mockModule->expects($this->once())
            ->method('getName')
            ->willReturn('TestModule');

        Module::shouldReceive('find')
            ->once()
            ->with('testmodule')
            ->andReturn($mockModule);

        Artisan::shouldReceive('call')
            ->with('module:migrate', ['module' => 'TestModule'])
            ->once();
        
        Artisan::shouldReceive('call')
            ->with('cache:clear')
            ->once();
        
        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once();

        $request = Request::create('/modules/enable/testmodule', 'POST');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ModulesController;
        $response = $controller->enable($request, 'testmodule');

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('success', $data['status']);
    }

    public function test_enable_returns_404_for_nonexistent_module(): void
    {
        Module::shouldReceive('find')
            ->once()
            ->with('nonexistent')
            ->andReturn(null);

        $request = Request::create('/modules/enable/nonexistent', 'POST');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ModulesController;
        $response = $controller->enable($request, 'nonexistent');

        $this->assertEquals(404, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('error', $data['status']);
    }

    public function test_enable_handles_exceptions_gracefully(): void
    {
        $mockModule = $this->createMock(\Nwidart\Modules\Module::class);
        $mockModule->expects($this->once())
            ->method('enable')
            ->willThrowException(new \Exception('Migration failed'));
        $mockModule->expects($this->once())
            ->method('getName')
            ->willReturn('TestModule');

        Module::shouldReceive('find')
            ->once()
            ->with('testmodule')
            ->andReturn($mockModule);

        Artisan::shouldReceive('call')
            ->with('module:migrate', ['module' => 'TestModule'])
            ->andThrow(new \Exception('Migration failed'));

        $request = Request::create('/modules/enable/testmodule', 'POST');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ModulesController;
        $response = $controller->enable($request, 'testmodule');

        $this->assertEquals(500, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('error', $data['status']);
    }

    public function test_disable_deactivates_module(): void
    {
        // Mock Module facade
        $mockModule = $this->createMock(\Nwidart\Modules\Module::class);
        $mockModule->expects($this->once())
            ->method('disable');
        $mockModule->expects($this->once())
            ->method('getName')
            ->willReturn('TestModule');

        Module::shouldReceive('find')
            ->once()
            ->with('testmodule')
            ->andReturn($mockModule);

        Artisan::shouldReceive('call')
            ->with('cache:clear')
            ->once();
        
        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once();

        $request = Request::create('/modules/disable/testmodule', 'POST');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ModulesController;
        $response = $controller->disable($request, 'testmodule');

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('success', $data['status']);
    }

    public function test_disable_returns_404_for_nonexistent_module(): void
    {
        Module::shouldReceive('find')
            ->once()
            ->with('nonexistent')
            ->andReturn(null);

        $request = Request::create('/modules/disable/nonexistent', 'POST');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ModulesController;
        $response = $controller->disable($request, 'nonexistent');

        $this->assertEquals(404, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('error', $data['status']);
    }

    public function test_disable_handles_exceptions_gracefully(): void
    {
        $mockModule = $this->createMock(\Nwidart\Modules\Module::class);
        $mockModule->expects($this->once())
            ->method('disable')
            ->willThrowException(new \Exception('Disable failed'));
        $mockModule->expects($this->once())
            ->method('getName')
            ->willReturn('TestModule');

        Module::shouldReceive('find')
            ->once()
            ->with('testmodule')
            ->andReturn($mockModule);

        $request = Request::create('/modules/disable/testmodule', 'POST');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ModulesController;
        $response = $controller->disable($request, 'testmodule');

        $this->assertEquals(500, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('error', $data['status']);
    }

    public function test_delete_removes_module_from_system(): void
    {
        // Mock Module facade
        $mockModule = $this->createMock(\Nwidart\Modules\Module::class);
        $mockModule->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $mockModule->expects($this->once())
            ->method('disable');
        $mockModule->expects($this->once())
            ->method('getPath')
            ->willReturn('/tmp/test-module-path');
        $mockModule->expects($this->once())
            ->method('getName')
            ->willReturn('TestModule');

        Module::shouldReceive('find')
            ->once()
            ->with('testmodule')
            ->andReturn($mockModule);

        File::shouldReceive('deleteDirectory')
            ->once()
            ->with('/tmp/test-module-path');

        Artisan::shouldReceive('call')
            ->with('cache:clear')
            ->once();
        
        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once();

        $request = Request::create('/modules/delete/testmodule', 'POST');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ModulesController;
        $response = $controller->delete($request, 'testmodule');

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('success', $data['status']);
    }

    public function test_delete_skips_disable_for_already_disabled_module(): void
    {
        $mockModule = $this->createMock(\Nwidart\Modules\Module::class);
        $mockModule->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $mockModule->expects($this->never())
            ->method('disable');
        $mockModule->expects($this->once())
            ->method('getPath')
            ->willReturn('/tmp/test-module-path');
        $mockModule->expects($this->once())
            ->method('getName')
            ->willReturn('TestModule');

        Module::shouldReceive('find')
            ->once()
            ->with('testmodule')
            ->andReturn($mockModule);

        File::shouldReceive('deleteDirectory')
            ->once()
            ->with('/tmp/test-module-path');

        Artisan::shouldReceive('call')
            ->with('cache:clear')
            ->once();
        
        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once();

        $request = Request::create('/modules/delete/testmodule', 'POST');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ModulesController;
        $response = $controller->delete($request, 'testmodule');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_delete_returns_404_for_nonexistent_module(): void
    {
        Module::shouldReceive('find')
            ->once()
            ->with('nonexistent')
            ->andReturn(null);

        $request = Request::create('/modules/delete/nonexistent', 'POST');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ModulesController;
        $response = $controller->delete($request, 'nonexistent');

        $this->assertEquals(404, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('error', $data['status']);
    }

    public function test_delete_handles_exceptions_gracefully(): void
    {
        $mockModule = $this->createMock(\Nwidart\Modules\Module::class);
        $mockModule->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);
        $mockModule->expects($this->once())
            ->method('getPath')
            ->willReturn('/tmp/test-module-path');
        $mockModule->expects($this->once())
            ->method('getName')
            ->willReturn('TestModule');

        Module::shouldReceive('find')
            ->once()
            ->with('testmodule')
            ->andReturn($mockModule);

        File::shouldReceive('deleteDirectory')
            ->once()
            ->with('/tmp/test-module-path')
            ->andThrow(new \Exception('Directory deletion failed'));

        $request = Request::create('/modules/delete/testmodule', 'POST');
        $request->setUserResolver(fn () => $this->admin);

        $controller = new ModulesController;
        $response = $controller->delete($request, 'testmodule');

        $this->assertEquals(500, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('error', $data['status']);
    }
}
