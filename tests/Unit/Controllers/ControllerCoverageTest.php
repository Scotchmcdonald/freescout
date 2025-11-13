<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;

class ControllerCoverageTest extends TestCase
{
    use RefreshDatabase;

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
    // SettingsController Tests (3 tests)
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

    // ========================================
    // UserController Tests (3 tests)
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

    // ========================================
    // ModulesController Tests (3 tests)
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
}
