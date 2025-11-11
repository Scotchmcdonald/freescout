<?php

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MailboxConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create();
        $this->regularUser = User::factory()->create();
        $this->mailbox = Mailbox::factory()->create();
    }

    #[Test]
    public function non_admins_cannot_view_connection_settings_pages()
    {
        $this->actingAs($this->regularUser);

        $this->get(route('mailboxes.connection.incoming', $this->mailbox))
            ->assertStatus(403);

        $this->get(route('mailboxes.connection.outgoing', $this->mailbox))
            ->assertStatus(403);
    }

    #[Test]
    public function non_admins_cannot_update_connection_settings()
    {
        $this->actingAs($this->regularUser);

        $this->post(route('mailboxes.connection.incoming', $this->mailbox), [])
            ->assertStatus(403);

        $this->post(route('mailboxes.connection.outgoing', $this->mailbox), [])
            ->assertStatus(403);
    }

    #[Test]
    public function admin_can_view_incoming_connection_settings_page()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('mailboxes.connection.incoming', $this->mailbox));

        $response->assertStatus(200);
        $response->assertViewIs('mailboxes.connection_incoming');
        $response->assertSee('Incoming Connection');
        $response->assertSee('Protocol');
        $response->assertSee('Server');
        $response->assertSee('Port');
        $response->assertViewHas('mailbox', $this->mailbox);
    }

    #[Test]
    public function admin_can_update_incoming_connection_settings()
    {
        $this->actingAs($this->adminUser);

        $data = [
            'in_protocol' => 'imap',
            'in_server' => 'imap.test.com',
            'in_port' => 993,
            'in_encryption' => 'ssl',
            'in_username' => 'testuser',
            'in_password' => 'newpassword',
        ];

        $response = $this->post(route('mailboxes.connection.incoming', $this->mailbox), $data);

        $response->assertRedirect(route('mailboxes.connection.incoming', $this->mailbox))
            ->assertSessionHas('success');

        $this->mailbox->refresh();

        $this->assertEquals($data['in_server'], $this->mailbox->in_server);
        $this->assertEquals($data['in_username'], $this->mailbox->in_username);
        $this->assertEquals($data['in_password'], Crypt::decrypt($this->mailbox->in_password));
    }

    #[Test]
    public function incoming_connection_validation_fails_with_invalid_data()
    {
        $this->actingAs($this->adminUser);

        $data = [
            'in_protocol' => 'invalid',
            'in_server' => '',
            'in_port' => 'not-a-number',
        ];

        $this->post(route('mailboxes.connection.incoming', $this->mailbox), $data)
            ->assertSessionHasErrors(['in_protocol', 'in_server', 'in_port']);
    }

    #[Test]
    public function admin_can_view_outgoing_connection_settings_page()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('mailboxes.connection.outgoing', $this->mailbox));

        $response->assertStatus(200);
        $response->assertViewIs('mailboxes.connection_outgoing');
        $response->assertSee('Outgoing Connection');
        $response->assertSee('SMTP');
        $response->assertSee('Server');
        $response->assertSee('Port');
        $response->assertViewHas('mailbox', $this->mailbox);
    }

    #[Test]
    public function admin_can_update_outgoing_connection_settings()
    {
        $this->actingAs($this->adminUser);

        $data = [
            'out_method' => 'smtp',
            'from_name' => 'Test From Name',
            'out_server' => 'smtp.test.com',
            'out_port' => 587,
            'out_encryption' => 'tls',
            'out_username' => 'smtpuser',
            'out_password' => 'new-smtp-password',
        ];

        $response = $this->post(route('mailboxes.connection.outgoing', $this->mailbox), $data);

        $response->assertRedirect(route('mailboxes.connection.outgoing', $this->mailbox))
            ->assertSessionHas('success');

        $this->mailbox->refresh();

        $this->assertEquals($data['out_server'], $this->mailbox->out_server);
        $this->assertEquals($data['out_username'], $this->mailbox->out_username);
        $this->assertEquals($data['out_password'], Crypt::decrypt($this->mailbox->out_password));
        $this->assertEquals(3, $this->mailbox->from_name);
        $this->assertEquals($data['from_name'], $this->mailbox->from_name_custom);
    }

    #[Test]
    public function outgoing_connection_validation_fails_with_invalid_data()
    {
        $this->actingAs($this->adminUser);

        $data = [
            'out_method' => 'invalid',
            'out_port' => 'not-a-number',
        ];

        $this->post(route('mailboxes.connection.outgoing', $this->mailbox), $data)
            ->assertSessionHasErrors(['out_method', 'out_port']);
    }
}
