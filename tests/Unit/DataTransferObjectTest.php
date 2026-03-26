<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DataTransferObjects\Action1DeviceDiscoveredData;
use App\DataTransferObjects\AssetCountChangedData;
use App\DataTransferObjects\AssetStatusChangedData;
use App\DataTransferObjects\BulkConversationData;
use App\DataTransferObjects\ClientCreatedData;
use App\DataTransferObjects\ClientUpdatedData;
use App\DataTransferObjects\CreateConversationData;
use App\DataTransferObjects\CustomerData;
use App\DataTransferObjects\DraftData;
use App\DataTransferObjects\GoogleChromebookDiscoveredData;
use App\DataTransferObjects\GoogleUserSyncedData;
use App\DataTransferObjects\MailboxData;
use App\DataTransferObjects\StoreRoleData;
use App\DataTransferObjects\ThreadData;
use App\DataTransferObjects\UserData;
use App\DataTransferObjects\UserStatusChangedData;
use App\Enums\ConversationStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Tests\PureUnitTestCase;

/**
 * DTO construction and factory-method coverage.
 * Pure unit — zero framework booting, zero DB.
 */
class DataTransferObjectTest extends PureUnitTestCase
{
    // ── Action1DeviceDiscoveredData ─────────────────────────────────────────

    public function test_action1_device_discovered_data_constructor(): void
    {
        $dto = new Action1DeviceDiscoveredData(
            clientId: 1,
            hostname: 'host-01',
            osType: 'windows',
            osVersion: '11',
            action1DeviceId: 'dev-abc',
            isOnline: true,
            assignedUserEmail: 'user@example.com',
            metadata: ['key' => 'value'],
        );

        $this->assertSame(1, $dto->clientId);
        $this->assertSame('host-01', $dto->hostname);
        $this->assertSame('windows', $dto->osType);
        $this->assertSame('11', $dto->osVersion);
        $this->assertSame('dev-abc', $dto->action1DeviceId);
        $this->assertTrue($dto->isOnline);
        $this->assertSame('user@example.com', $dto->assignedUserEmail);
        $this->assertSame(['key' => 'value'], $dto->metadata);
    }

    public function test_action1_device_discovered_data_from_array_snake_case(): void
    {
        $dto = Action1DeviceDiscoveredData::fromArray([
            'client_id' => 7,
            'hostname' => 'srv-01',
            'os_type' => 'linux',
            'os_version' => 'Ubuntu 22',
            'action1_device_id' => 'd123',
            'is_online' => false,
            'assigned_user_email' => null,
            'metadata' => [],
        ]);

        $this->assertSame(7, $dto->clientId);
        $this->assertSame('srv-01', $dto->hostname);
        $this->assertFalse($dto->isOnline);
        $this->assertNull($dto->assignedUserEmail);
    }

    public function test_action1_device_discovered_data_from_array_camel_case(): void
    {
        $dto = Action1DeviceDiscoveredData::fromArray([
            'client_id' => 3,
            'hostname' => 'mac-02',
            'osType' => 'macos',
            'osVersion' => '14',
            'action1DeviceId' => 'd456',
            'isOnline' => true,
        ]);

        $this->assertSame('macos', $dto->osType);
        $this->assertTrue($dto->isOnline);
    }

    public function test_action1_device_discovered_data_to_array(): void
    {
        $dto = new Action1DeviceDiscoveredData(1, 'h', 'linux', '6', 'id', true, null, []);
        $arr = $dto->toArray();
        $this->assertArrayHasKey('client_id', $arr);
        $this->assertArrayHasKey('is_online', $arr);
    }

    // ── AssetCountChangedData ──────────────────────────────────────────────

    public function test_asset_count_changed_data_constructor(): void
    {
        $dto = new AssetCountChangedData(
            clientId: 5,
            assetType: 'chromebook',
            previousCount: 10,
            newCount: 15,
            changeReason: 'asset_added',
            assetId: 42,
        );

        $this->assertSame(5, $dto->clientId);
        $this->assertSame(10, $dto->previousCount);
        $this->assertSame(15, $dto->newCount);
        $this->assertSame(42, $dto->assetId);
    }

    public function test_asset_count_changed_get_delta(): void
    {
        $dto = new AssetCountChangedData(1, 'windows', 10, 15, 'added', null);
        $this->assertSame(5, $dto->getDelta());

        $dto2 = new AssetCountChangedData(1, 'windows', 15, 10, 'removed', null);
        $this->assertSame(-5, $dto2->getDelta());
    }

    public function test_asset_count_changed_is_increase_and_decrease(): void
    {
        $increase = new AssetCountChangedData(1, 'windows', 5, 10, 'add', null);
        $this->assertTrue($increase->isIncrease());
        $this->assertFalse($increase->isDecrease());

        $decrease = new AssetCountChangedData(1, 'windows', 10, 5, 'remove', null);
        $this->assertFalse($decrease->isIncrease());
        $this->assertTrue($decrease->isDecrease());

        $same = new AssetCountChangedData(1, 'windows', 5, 5, 'reconcile', null);
        $this->assertFalse($same->isIncrease());
        $this->assertFalse($same->isDecrease());
    }

    public function test_asset_count_changed_from_array(): void
    {
        $dto = AssetCountChangedData::fromArray([
            'client_id' => 2,
            'asset_type' => 'linux',
            'previous_count' => 3,
            'new_count' => 4,
            'change_reason' => 'sync',
        ]);

        $this->assertSame(2, $dto->clientId);
        $this->assertSame(1, $dto->getDelta());
        $this->assertNull($dto->assetId);
    }

    public function test_asset_count_changed_to_array(): void
    {
        $dto = new AssetCountChangedData(1, 'type', 0, 1, 'reason', null);
        $arr = $dto->toArray();
        $this->assertArrayHasKey('asset_type', $arr);
        $this->assertArrayHasKey('change_reason', $arr);
    }

    // ── AssetStatusChangedData ─────────────────────────────────────────────

    public function test_asset_status_changed_data_constructor(): void
    {
        $dto = new AssetStatusChangedData(
            assetId: 10,
            clientId: 2,
            oldStatus: 'active',
            newStatus: 'offline',
            source: 'GoogleAdmin',
            userId: 99,
        );

        $this->assertSame(10, $dto->assetId);
        $this->assertSame('active', $dto->oldStatus);
        $this->assertSame('offline', $dto->newStatus);
        $this->assertSame('GoogleAdmin', $dto->source);
    }

    public function test_asset_status_changed_data_from_array_with_null_old_status(): void
    {
        $dto = AssetStatusChangedData::fromArray([
            'asset_id' => 5,
            'client_id' => 1,
            'new_status' => 'online',
            'source' => 'Action1',
        ]);

        $this->assertNull($dto->oldStatus);
        $this->assertSame('online', $dto->newStatus);
    }

    public function test_asset_status_changed_data_to_array(): void
    {
        $dto = new AssetStatusChangedData(1, 1, null, 'online', 'Manual', null);
        $arr = $dto->toArray();
        $this->assertArrayHasKey('new_status', $arr);
        $this->assertNull($arr['old_status']);
    }

    // ── BulkConversationData ───────────────────────────────────────────────

    public function test_bulk_conversation_data_constructor(): void
    {
        $dto = new BulkConversationData(
            conversationIds: [1, 2, 3],
            action: 'change_status',
            status: ConversationStatus::Closed,
        );

        $this->assertSame([1, 2, 3], $dto->conversationIds);
        $this->assertSame('change_status', $dto->action);
        $this->assertSame(ConversationStatus::Closed, $dto->status);
    }

    public function test_bulk_conversation_data_from_array_with_status(): void
    {
        $dto = BulkConversationData::fromArray([
            'conversation_ids' => [4, 5],
            'action' => 'delete',
            'status' => 3, // ConversationStatus::Closed->value
        ]);

        $this->assertSame([4, 5], $dto->conversationIds);
        $this->assertSame(ConversationStatus::Closed, $dto->status);
    }

    public function test_bulk_conversation_data_from_array_without_status(): void
    {
        $dto = BulkConversationData::fromArray([
            'conversation_ids' => [1],
            'action' => 'assign',
            'user_id' => 7,
        ]);

        $this->assertNull($dto->status);
        $this->assertSame(7, $dto->userId);
    }

    // ── ClientCreatedData ──────────────────────────────────────────────────

    public function test_client_created_data_constructor(): void
    {
        $dto = new ClientCreatedData(
            clientId: 20,
            name: 'Acme Corp',
            companyId: 5,
            billingEmail: 'billing@acme.com',
            metadata: ['tier' => 'pro'],
        );

        $this->assertSame(20, $dto->clientId);
        $this->assertSame('Acme Corp', $dto->name);
        $this->assertSame(5, $dto->companyId);
        $this->assertSame('billing@acme.com', $dto->billingEmail);
        $this->assertSame(['tier' => 'pro'], $dto->metadata);
    }

    public function test_client_created_data_nullable_company_id(): void
    {
        $dto = new ClientCreatedData(1, 'Test', null, 'a@b.com', []);
        $this->assertNull($dto->companyId);
    }

    // ── ClientUpdatedData ──────────────────────────────────────────────────

    public function test_client_updated_data_constructor(): void
    {
        $dto = new ClientUpdatedData(
            clientId: 3,
            changedFields: ['name', 'email'],
            oldValues: ['name' => 'Old Name'],
        );

        $this->assertSame(3, $dto->clientId);
        $this->assertSame(['name', 'email'], $dto->changedFields);
        $this->assertSame(['name' => 'Old Name'], $dto->oldValues);
    }

    // ── CreateConversationData ─────────────────────────────────────────────

    public function test_create_conversation_data_from_array(): void
    {
        $dto = CreateConversationData::fromArray([
            'subject' => 'Help needed',
            'body' => 'Please help',
            'to' => ['support@example.com'],
            'customer_id' => 10,
            'assign_to' => 3,
        ]);

        $this->assertSame('Help needed', $dto->subject);
        $this->assertSame(['support@example.com'], $dto->to);
        $this->assertSame(10, $dto->customerId);
        $this->assertSame(3, $dto->assignTo);
    }

    public function test_create_conversation_data_from_array_defaults(): void
    {
        $dto = CreateConversationData::fromArray([
            'subject' => 'Test',
            'body' => 'Body',
            'to' => ['a@b.com'],
        ]);

        $this->assertNull($dto->customerId);
        $this->assertNull($dto->status);
        $this->assertNull($dto->assignTo);
    }

    // ── CustomerData ───────────────────────────────────────────────────────

    public function test_customer_data_constructor_minimal(): void
    {
        $dto = new CustomerData(firstName: 'Jane');
        $this->assertSame('Jane', $dto->firstName);
        $this->assertNull($dto->lastName);
        $this->assertNull($dto->email);
    }

    public function test_customer_data_constructor_full(): void
    {
        $dto = new CustomerData(
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            company: 'Acme',
            jobTitle: 'CEO',
            phone: '+1555',
            timezone: 'UTC',
            address: '1 Main St',
            city: 'NYC',
            state: 'NY',
            zip: '10001',
            country: 'US',
        );

        $this->assertSame('John', $dto->firstName);
        $this->assertSame('Doe', $dto->lastName);
        $this->assertSame('john@example.com', $dto->email);
        $this->assertSame('Acme', $dto->company);
        $this->assertSame('NYC', $dto->city);
    }

    // ── DraftData ──────────────────────────────────────────────────────────

    public function test_draft_data_constructor(): void
    {
        $dto = new DraftData(
            conversationId: 99,
            userId: 7,
            body: 'Draft body',
            to: '["a@b.com"]',
            cc: null,
            bcc: null,
            attachmentIds: [1, 2],
        );

        $this->assertSame(99, $dto->conversationId);
        $this->assertSame(7, $dto->userId);
        $this->assertSame('Draft body', $dto->body);
        $this->assertSame([1, 2], $dto->attachmentIds);
        $this->assertNull($dto->bcc);
    }

    // ── GoogleChromebookDiscoveredData ─────────────────────────────────────

    public function test_google_chromebook_discovered_data_constructor(): void
    {
        $dto = new GoogleChromebookDiscoveredData(
            clientId: 1,
            serialNumber: 'SN123',
            model: 'Chromebook Pro',
            status: 'active',
            assignedUserEmail: 'user@school.com',
            metadata: [],
        );

        $this->assertSame(1, $dto->clientId);
        $this->assertSame('SN123', $dto->serialNumber);
        $this->assertSame('active', $dto->status);
    }

    public function test_google_chromebook_from_array(): void
    {
        $dto = GoogleChromebookDiscoveredData::fromArray([
            'client_id' => 2,
            'serial_number' => 'SN456',
            'model' => 'Acer C738T',
            'status' => 'deprovisioned',
            'assigned_user_email' => null,
            'metadata' => [],
        ]);

        $this->assertSame(2, $dto->clientId);
        $this->assertSame('deprovisioned', $dto->status);
        $this->assertNull($dto->assignedUserEmail);
    }

    public function test_google_chromebook_to_array(): void
    {
        $dto = new GoogleChromebookDiscoveredData(1, 'SN', 'Model', 'active', null, []);
        $arr = $dto->toArray();
        $this->assertArrayHasKey('serial_number', $arr);
    }

    // ── GoogleUserSyncedData ───────────────────────────────────────────────

    public function test_google_user_synced_data_constructor(): void
    {
        $dto = new GoogleUserSyncedData(
            clientId: 3,
            email: 'guser@domain.com',
            firstName: 'Alice',
            lastName: 'Smith',
            googleId: 'g-id-123',
            suspended: false,
            orgUnitPath: '/IT',
            metadata: [],
        );

        $this->assertSame('guser@domain.com', $dto->email);
        $this->assertFalse($dto->suspended);
        $this->assertSame('/IT', $dto->orgUnitPath);
    }

    public function test_google_user_synced_from_array(): void
    {
        $dto = GoogleUserSyncedData::fromArray([
            'client_id' => 5,
            'email' => 'bob@test.com',
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'google_id' => 'gid-789',
            'suspended' => true,
            'org_unit_path' => '/',
            'metadata' => ['dep' => 'eng'],
        ]);

        $this->assertTrue($dto->suspended);
        $this->assertSame(['dep' => 'eng'], $dto->metadata);
    }

    public function test_google_user_synced_to_array(): void
    {
        $dto = new GoogleUserSyncedData(1, 'e@e.com', 'F', 'L', 'gid', false, '/', []);
        $arr = $dto->toArray();
        $this->assertArrayHasKey('google_id', $arr);
    }

    // ── MailboxData ────────────────────────────────────────────────────────

    public function test_mailbox_data_constructor(): void
    {
        $dto = new MailboxData(
            name: 'Support',
            email: 'support@company.com',
            inServer: 'imap.company.com',
            inPort: 993,
            inUsername: 'support',
            inPassword: 'secret',
            inProtocol: 'imap',
            inEncryption: 'ssl',
            outServer: 'smtp.company.com',
            outPort: 587,
            outUsername: 'support',
            outPassword: 'secret',
            outEncryption: 'tls',
            outMethod: 'smtp',
            autoReplyEnabled: false,
            autoReplySubject: null,
            autoReplyMessage: null,
        );

        $this->assertSame('Support', $dto->name);
        $this->assertSame(993, $dto->inPort);
        $this->assertSame('ssl', $dto->inEncryption);
        $this->assertFalse($dto->autoReplyEnabled);
    }

    public function test_mailbox_data_from_array(): void
    {
        $dto = MailboxData::fromArray([
            'name' => 'Test',
            'email' => 'test@test.com',
            'in_server' => 'imap.test.com',
            'in_port' => 993,
            'out_server' => 'smtp.test.com',
            'auto_reply_enabled' => true,
        ]);

        $this->assertSame('Test', $dto->name);
        $this->assertSame('imap.test.com', $dto->inServer);
        $this->assertTrue($dto->autoReplyEnabled);
    }

    public function test_mailbox_data_has_incoming_config(): void
    {
        $with = MailboxData::fromArray(['name' => 'A', 'email' => 'a@b.com', 'in_server' => 'imap.x.com']);
        $without = MailboxData::fromArray(['name' => 'B', 'email' => 'b@c.com']);

        $this->assertTrue($with->hasIncomingConfig());
        $this->assertFalse($without->hasIncomingConfig());
    }

    public function test_mailbox_data_has_outgoing_config(): void
    {
        $with = MailboxData::fromArray(['name' => 'A', 'email' => 'a@b.com', 'out_server' => 'smtp.x.com']);
        $without = MailboxData::fromArray(['name' => 'B', 'email' => 'b@c.com']);

        $this->assertTrue($with->hasOutgoingConfig());
        $this->assertFalse($without->hasOutgoingConfig());
    }

    public function test_mailbox_data_to_array(): void
    {
        $dto = new MailboxData('n', 'e@e.com');
        $arr = $dto->toArray();
        $this->assertArrayHasKey('name', $arr);
        $this->assertArrayHasKey('email', $arr);
    }

    // ── StoreRoleData ──────────────────────────────────────────────────────

    public function test_store_role_data_constructor(): void
    {
        $dto = new StoreRoleData(
            name: 'billing_manager',
            label: 'Billing Manager',
            scope: 'internal',
        );

        $this->assertSame('billing_manager', $dto->name);
        $this->assertSame('Billing Manager', $dto->label);
        $this->assertSame('internal', $dto->scope);
    }

    // ── ThreadData ─────────────────────────────────────────────────────────

    public function test_thread_data_constructor_defaults(): void
    {
        $dto = new ThreadData(body: 'Hello');
        $this->assertSame('Hello', $dto->body);
        $this->assertSame(1, $dto->type);
        $this->assertFalse($dto->isDraft);
        $this->assertSame([], $dto->to);
    }

    public function test_thread_data_constructor_full(): void
    {
        $dto = new ThreadData(
            body: 'Note',
            type: 2,
            status: null,
            to: ['a@b.com'],
            cc: [],
            bcc: [],
            attachmentPaths: ['/tmp/file.pdf'],
            isDraft: false,
        );

        $this->assertSame(2, $dto->type);
        $this->assertSame(['a@b.com'], $dto->to);
        $this->assertSame(['/tmp/file.pdf'], $dto->attachmentPaths);
    }

    public function test_thread_data_is_note(): void
    {
        $dto = new ThreadData(body: 'note', type: 2);
        $this->assertTrue($dto->isNote());
        $this->assertFalse($dto->isReply());
    }

    public function test_thread_data_is_reply(): void
    {
        $dto = new ThreadData(body: 'reply', type: 1);
        $this->assertTrue($dto->isReply());
        $this->assertFalse($dto->isNote());
    }

    public function test_thread_data_has_attachments(): void
    {
        $with = new ThreadData(body: 'x', attachmentPaths: ['/a.pdf']);
        $without = new ThreadData(body: 'x');
        $this->assertTrue($with->hasAttachments());
        $this->assertFalse($without->hasAttachments());
    }

    public function test_thread_data_to_array(): void
    {
        $dto = new ThreadData(body: 'test', type: 1, to: ['x@y.com']);
        $arr = $dto->toArray();
        $this->assertArrayHasKey('body', $arr);
        $this->assertArrayHasKey('type', $arr);
    }

    // ── UserData ───────────────────────────────────────────────────────────

    public function test_user_data_from_array_minimal(): void
    {
        $dto = UserData::fromArray([
            'first_name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $this->assertSame('Alice', $dto->firstName);
        $this->assertSame('alice@example.com', $dto->email);
        $this->assertNull($dto->lastName);
        $this->assertSame(UserRole::User, $dto->role);
        $this->assertSame(UserStatus::Active, $dto->status);
    }

    public function test_user_data_from_array_full(): void
    {
        $dto = UserData::fromArray([
            'first_name' => 'Bob',
            'last_name' => 'Smith',
            'email' => 'bob@example.com',
            'password' => 'secret',
            'role' => UserRole::Admin->value,
            'status' => UserStatus::Inactive->value,
            'job_title' => 'Manager',
        ]);

        $this->assertSame('Bob', $dto->firstName);
        $this->assertSame('Smith', $dto->lastName);
        $this->assertSame(UserRole::Admin, $dto->role);
        $this->assertSame(UserStatus::Inactive, $dto->status);
        $this->assertSame('Manager', $dto->jobTitle);
    }

    // ── UserStatusChangedData ──────────────────────────────────────────────

    public function test_user_status_changed_data_constructor(): void
    {
        $dto = new UserStatusChangedData(
            userId: 42,
            clientId: 10,
            email: 'user@example.com',
            oldStatus: 'active',
            newStatus: 'inactive',
            reason: 'manual',
        );

        $this->assertSame(42, $dto->userId);
        $this->assertSame(10, $dto->clientId);
        $this->assertSame('user@example.com', $dto->email);
        $this->assertSame('active', $dto->oldStatus);
        $this->assertSame('inactive', $dto->newStatus);
        $this->assertSame('manual', $dto->reason);
    }

    public function test_user_status_changed_data_nullable_reason(): void
    {
        $dto = new UserStatusChangedData(1, 2, 'e@e.com', 'active', 'inactive', null);
        $this->assertNull($dto->reason);
    }
}
