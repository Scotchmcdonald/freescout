<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for mailbox advanced settings functionality.
 *
 * @covers \App\Http\Controllers\MailboxController::advancedSettings
 * @covers \App\Http\Controllers\MailboxController::saveAdvancedSettings
 */
class MailboxAdvancedSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->regularUser = User::factory()->create(['role' => User::ROLE_USER]);
        $this->mailbox = Mailbox::factory()->create();
        
        // Give regular user access to mailbox
        $this->mailbox->users()->attach($this->regularUser->id, ['access' => 10]);
    }

    // ====================
    // AUTHORIZATION TESTS
    // ====================

    public function test_admin_can_access_advanced_settings(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('mailboxes.advanced_settings', $this->mailbox));

        $response->assertStatus(200);
        $response->assertViewIs('mailboxes.advanced_settings');
    }

    public function test_regular_user_cannot_access_advanced_settings_of_unassigned_mailbox(): void
    {
        $otherMailbox = Mailbox::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get(route('mailboxes.advanced_settings', $otherMailbox));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('mailboxes.advanced_settings', $this->mailbox));

        $response->assertRedirect(route('login'));
    }

    // ====================
    // VIEW DATA TESTS
    // ====================

    public function test_advanced_settings_view_has_from_name_options(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('mailboxes.advanced_settings', $this->mailbox));

        $response->assertViewHas('fromNameOptions');
        
        $fromNameOptions = $response->viewData('fromNameOptions');
        $this->assertArrayHasKey(1, $fromNameOptions);
        $this->assertArrayHasKey(4, $fromNameOptions);
    }

    public function test_advanced_settings_view_has_ticket_assignee_options(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('mailboxes.advanced_settings', $this->mailbox));

        $response->assertViewHas('ticketAssigneeOptions');
        
        $ticketAssigneeOptions = $response->viewData('ticketAssigneeOptions');
        $this->assertArrayHasKey(1, $ticketAssigneeOptions);
        $this->assertArrayHasKey(2, $ticketAssigneeOptions);
    }

    // ====================
    // SAVE SETTINGS TESTS
    // ====================

    public function test_save_email_aliases(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('mailboxes.save_advanced_settings', $this->mailbox), [
                'aliases' => "alias1@example.com\nalias2@example.com",
                'aliases_reply' => 1,
                'from_name' => 1,
            ]);

        $response->assertRedirect(route('mailboxes.advanced_settings', $this->mailbox));
        
        $this->mailbox->refresh();
        $this->assertEquals('alias1@example.com,alias2@example.com', $this->mailbox->aliases);
        $this->assertTrue($this->mailbox->aliases_reply);
    }

    public function test_save_invalid_aliases_are_filtered(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('mailboxes.save_advanced_settings', $this->mailbox), [
                'aliases' => "valid@example.com\ninvalid-email\nalso-valid@test.com",
                'from_name' => 1,
            ]);

        $response->assertRedirect(route('mailboxes.advanced_settings', $this->mailbox));
        
        $this->mailbox->refresh();
        $this->assertEquals('valid@example.com,also-valid@test.com', $this->mailbox->aliases);
    }

    public function test_save_from_name_options(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('mailboxes.save_advanced_settings', $this->mailbox), [
                'from_name' => 4,
                'from_name_custom' => 'Custom Name',
            ]);

        $response->assertRedirect(route('mailboxes.advanced_settings', $this->mailbox));
        
        $this->mailbox->refresh();
        $this->assertEquals(4, $this->mailbox->from_name);
        $this->assertEquals('Custom Name', $this->mailbox->from_name_custom);
    }

    public function test_custom_from_name_required_when_from_name_is_custom(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('mailboxes.save_advanced_settings', $this->mailbox), [
                'from_name' => 4,
                'from_name_custom' => '',
            ]);

        $response->assertSessionHasErrors('from_name_custom');
    }

    public function test_save_ticket_assignment_options(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('mailboxes.save_advanced_settings', $this->mailbox), [
                'from_name' => 1,
                'ticket_assignee' => 2,
            ]);

        $response->assertRedirect(route('mailboxes.advanced_settings', $this->mailbox));
        
        $this->mailbox->refresh();
        $this->assertEquals(2, $this->mailbox->ticket_assignee);
    }

    public function test_save_signature(): void
    {
        $signature = "Best regards,\nSupport Team";
        
        $response = $this->actingAs($this->adminUser)
            ->post(route('mailboxes.save_advanced_settings', $this->mailbox), [
                'from_name' => 1,
                'signature' => $signature,
            ]);

        $response->assertRedirect(route('mailboxes.advanced_settings', $this->mailbox));
        
        $this->mailbox->refresh();
        $this->assertEquals($signature, $this->mailbox->signature);
    }

    public function test_save_before_reply_text(): void
    {
        $beforeReply = "--- Original Message ---";
        
        $response = $this->actingAs($this->adminUser)
            ->post(route('mailboxes.save_advanced_settings', $this->mailbox), [
                'from_name' => 1,
                'before_reply' => $beforeReply,
            ]);

        $response->assertRedirect(route('mailboxes.advanced_settings', $this->mailbox));
        
        $this->mailbox->refresh();
        $this->assertEquals($beforeReply, $this->mailbox->before_reply);
    }

    public function test_save_ratings_toggle(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('mailboxes.save_advanced_settings', $this->mailbox), [
                'from_name' => 1,
                'ratings' => 1,
            ]);

        $response->assertRedirect(route('mailboxes.advanced_settings', $this->mailbox));
        
        $this->mailbox->refresh();
        $this->assertTrue($this->mailbox->ratings);
    }

    // ====================
    // VALIDATION TESTS
    // ====================

    public function test_from_name_must_be_valid_option(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('mailboxes.save_advanced_settings', $this->mailbox), [
                'from_name' => 99,
            ]);

        $response->assertSessionHasErrors('from_name');
    }

    public function test_ticket_assignee_must_be_valid_option(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('mailboxes.save_advanced_settings', $this->mailbox), [
                'from_name' => 1,
                'ticket_assignee' => 99,
            ]);

        $response->assertSessionHasErrors('ticket_assignee');
    }

    public function test_aliases_max_length(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('mailboxes.save_advanced_settings', $this->mailbox), [
                'from_name' => 1,
                'aliases' => str_repeat('a', 1001),
            ]);

        $response->assertSessionHasErrors('aliases');
    }

    public function test_signature_max_length(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('mailboxes.save_advanced_settings', $this->mailbox), [
                'from_name' => 1,
                'signature' => str_repeat('a', 10001),
            ]);

        $response->assertSessionHasErrors('signature');
    }
}
