<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Http\Controllers\MailboxController;
use App\Models\Mailbox;
use App\Models\User;
use App\Services\ImapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class MailboxControllerEnhancedTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_view_with_mailboxes(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Mailbox::factory()->count(2)->create();

        $request = Request::create('/mailboxes', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new MailboxController;
        $view = $controller->index($request);

        $this->assertEquals('mailboxes.index', $view->name());
        $this->assertArrayHasKey('mailboxes', $view->getData());
    }

    public function test_show_returns_view_with_mailbox(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();

        $request = Request::create('/mailboxes/'.$mailbox->id, 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new MailboxController;
        $view = $controller->show($request, $mailbox);

        $this->assertEquals('mailboxes.show', $view->name());
    }

    public function test_settings_returns_view(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();

        $request = Request::create('/mailboxes/'.$mailbox->id.'/settings', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new MailboxController;
        $view = $controller->settings($request, $mailbox);

        $this->assertEquals('mailboxes.settings', $view->name());
    }

    public function test_connection_incoming_requires_authorization(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();

        $request = Request::create('/mailboxes/'.$mailbox->id.'/connection-incoming', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new MailboxController;

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $controller->connectionIncoming($request, $mailbox);
    }

    public function test_connection_outgoing_requires_authorization(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();

        $request = Request::create('/mailboxes/'.$mailbox->id.'/connection-outgoing', 'GET');
        $request->setUserResolver(fn () => $user);

        $controller = new MailboxController;

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $controller->connectionOutgoing($request, $mailbox);
    }

    public function test_fetch_emails_returns_json_response(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create([
            'in_server' => 'imap.example.com',
            'in_port' => 993,
            'in_username' => 'test@example.com',
            'in_password' => 'password',
        ]);
        $user->mailboxes()->attach($mailbox->id);

        $request = Request::create('/mailboxes/'.$mailbox->id.'/fetch', 'POST');
        $request->setUserResolver(fn () => $user);

        $imapService = $this->createMock(ImapService::class);
        $imapService->method('fetchEmails')->willReturn([]);

        $controller = new MailboxController;
        $response = $controller->fetchEmails($request, $mailbox, $imapService);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
    }
}
