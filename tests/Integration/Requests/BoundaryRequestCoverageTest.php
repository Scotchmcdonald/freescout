<?php

declare(strict_types=1);

namespace Tests\Integration\Requests;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\SaveDraftRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\StoreWebhookChannelRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\ValidateSmtpRequest;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tests\IntegrationTestCase;

class BoundaryRequestCoverageTest extends IntegrationTestCase
{
    private function makeUserRoute(User $target): object
    {
        return new class($target)
        {
            public function __construct(private User $target) {}

            public function parameter(string $name, mixed $default = null): mixed
            {
                return $name === 'user' ? $this->target : $default;
            }
        };
    }

    public function test_store_user_request_denies_unauthorized_user(): void
    {
        $request = StoreUserRequest::create('/users', 'POST');
        $request->setUserResolver(fn () => User::factory()->create(['role' => User::ROLE_USER]));

        $this->assertFalse($request->authorize());
    }

    public function test_store_user_request_requires_client_role_for_external_users(): void
    {
        $request = StoreUserRequest::create('/users', 'POST', [
            'first_name' => 'Client',
            'email' => 'client@example.com',
            'password' => 'password123',
            'type' => 2,
            'status' => UserStatus::Active->value,
        ]);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('client_role', $validator->errors()->toArray());
    }

    public function test_store_user_request_rejects_invalid_internal_role(): void
    {
        $request = StoreUserRequest::create('/users', 'POST', [
            'first_name' => 'Internal',
            'email' => 'internal@example.com',
            'password' => 'password123',
            'type' => 1,
            'role' => 999,
            'status' => UserStatus::Active->value,
        ]);

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('role', $validator->errors()->toArray());
    }

    public function test_update_user_request_denies_user_updating_someone_else(): void
    {
        $actor = User::factory()->create(['role' => User::ROLE_USER]);
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        $request = UpdateUserRequest::create('/users/'.$target->id, 'PUT');
        $request->setUserResolver(fn () => $actor);
        $request->setRouteResolver(fn () => $this->makeUserRoute($target));

        $this->assertFalse($request->authorize());
    }

    public function test_update_user_request_allows_current_email_but_rejects_other_users_email_and_unknown_mailbox(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $target = User::factory()->create(['email' => 'target@example.com']);
        $other = User::factory()->create(['email' => 'taken@example.com']);
        $mailbox = Mailbox::factory()->create();

        $request = UpdateUserRequest::create('/users/'.$target->id, 'PUT', [
            'first_name' => 'Updated',
            'email' => 'target@example.com',
            'type' => 1,
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
            'mailboxes' => [$mailbox->id],
        ]);
        $request->setUserResolver(fn () => $admin);
        $request->setRouteResolver(fn () => $this->makeUserRoute($target));

        $validator = Validator::make($request->all(), $request->rules(), $request->messages());
        $this->assertFalse($validator->fails());

        $duplicateEmailRequest = UpdateUserRequest::create('/users/'.$target->id, 'PUT', [
            'first_name' => 'Updated',
            'email' => $other->email,
            'type' => 1,
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
            'mailboxes' => [999999],
        ]);
        $duplicateEmailRequest->setUserResolver(fn () => $admin);
        $duplicateEmailRequest->setRouteResolver(fn () => $this->makeUserRoute($target));

        $duplicateValidator = Validator::make(
            $duplicateEmailRequest->all(),
            $duplicateEmailRequest->rules(),
            $duplicateEmailRequest->messages()
        );

        $this->assertTrue($duplicateValidator->fails());
        $this->assertArrayHasKey('email', $duplicateValidator->errors()->toArray());
        $this->assertArrayHasKey('mailboxes.0', $duplicateValidator->errors()->toArray());
    }

    public function test_save_draft_request_requires_existing_conversation_and_array_attachments(): void
    {
        $conversation = Conversation::factory()->create();
        $request = SaveDraftRequest::create('/drafts', 'POST', [
            'conversation_id' => $conversation->id,
            'body' => 'draft body',
            'attachment_ids' => 'not-an-array',
        ]);

        $validator = Validator::make($request->all(), $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('attachment_ids', $validator->errors()->toArray());

        $missingConversationRequest = SaveDraftRequest::create('/drafts', 'POST', [
            'conversation_id' => 999999,
        ]);
        $missingConversationValidator = Validator::make(
            $missingConversationRequest->all(),
            $missingConversationRequest->rules()
        );

        $this->assertTrue($missingConversationValidator->fails());
        $this->assertArrayHasKey('conversation_id', $missingConversationValidator->errors()->toArray());
    }

    public function test_store_webhook_channel_request_rejects_malformed_url_and_duration_bounds(): void
    {
        $request = StoreWebhookChannelRequest::create('/webhook-channels', 'POST', [
            'resource_type' => 'directory',
            'resource_id' => 'abc',
            'webhook_url' => 'not-a-url',
            'duration_hours' => 0,
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('webhook_url', $validator->errors()->toArray());
        $this->assertArrayHasKey('duration_hours', $validator->errors()->toArray());
    }

    public function test_validate_smtp_request_enforces_port_and_encryption_boundaries(): void
    {
        $request = ValidateSmtpRequest::create('/settings/email/validate', 'POST', [
            'out_server' => 'smtp.example.com',
            'out_port' => 70000,
            'email' => 'not-an-email',
            'out_encryption' => 9,
        ]);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('out_port', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('out_encryption', $validator->errors()->toArray());
    }
}
