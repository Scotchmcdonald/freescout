<?php

declare(strict_types=1);

namespace Tests\Integration\PIB;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Modules\Crm\Events\ConversationLinkedToClient;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\ClientConversation;
use Modules\Crm\Models\Company;
use Modules\PIB\Listeners\ConversationLinkedToClientListener;
use Modules\ContractManager\Models\BillingTemplate;
use Modules\PIB\Models\ConversationBillingMetadata;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * ConversationBillingMetadata Integration Tests
 * 
 * Tests billing metadata creation when conversations are linked to clients.
 * This tests the Core Blindness pattern: CRM fires events, PIB handles billing logic.
 */
#[Group('integration')]
#[Group('pib')]
#[Group('billing')]
class ConversationBillingMetadataTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createRequiredTables();

        $this->company = Company::factory()->create();
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
    }

    private function createRequiredTables(): void
    {
        Schema::dropIfExists('conversation_billing_metadata');
        Schema::dropIfExists('client_conversations');
        Schema::dropIfExists('billing_templates');
        Schema::dropIfExists('conversations');

        Schema::create('conversations', function ($table) {
            $table->id();
            $table->string('customer_email')->nullable();
            $table->string('subject')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('client_conversations', function ($table) {
            $table->id();
            $table->foreignId('client_id');
            $table->foreignId('conversation_id');
            $table->integer('total_time_minutes')->default(0);
            $table->foreignId('linked_by_user_id')->nullable();
            $table->string('linked_via')->default('manual');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['client_id', 'conversation_id']);
        });

        Schema::create('billing_templates', function ($table) {
            $table->id();
            $table->foreignId('company_id');
            $table->foreignId('client_id');
            $table->string('product_type');
            $table->string('status')->default('active');
            $table->json('product_config')->nullable();
            $table->timestamps();
        });

        Schema::create('conversation_billing_metadata', function ($table) {
            $table->id();
            $table->foreignId('client_conversation_id')->unique();
            $table->string('billing_category')->default('ad_hoc');
            $table->boolean('is_billable')->default(true);
            $table->integer('billable_time_minutes')->default(0);
            $table->foreignId('invoice_id')->nullable();
            $table->timestamp('invoiced_at')->nullable();
            $table->timestamps();
        });
    }

    private function createConversation(array $attributes = []): int
    {
        return DB::table('conversations')->insertGetId(array_merge([
            'customer_email' => 'customer@example.com',
            'subject' => 'Test Ticket',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    private function createClientConversation(int $clientId, int $conversationId): ClientConversation
    {
        return ClientConversation::create([
            'client_id' => $clientId,
            'conversation_id' => $conversationId,
            'linked_via' => ClientConversation::LINKED_VIA_MANUAL,
        ]);
    }

    /**
     * Test billing category constants.
     */
    public function test_billing_category_constants(): void
    {
        $this->assertEquals('included', ConversationBillingMetadata::CATEGORY_INCLUDED);
        $this->assertEquals('ad_hoc', ConversationBillingMetadata::CATEGORY_AD_HOC);
        $this->assertEquals('warranty', ConversationBillingMetadata::CATEGORY_WARRANTY);
        $this->assertEquals('project', ConversationBillingMetadata::CATEGORY_PROJECT);
        $this->assertEquals('emergency', ConversationBillingMetadata::CATEGORY_EMERGENCY);
    }

    /**
     * Test listener creates billing metadata on conversation linked.
     */
    public function test_listener_creates_billing_metadata(): void
    {
        $conversationId = $this->createConversation();
        $clientConversation = $this->createClientConversation($this->client->id, $conversationId);

        $event = new ConversationLinkedToClient(
            conversationId: $conversationId,
            clientId: $this->client->id,
            clientConversationId: $clientConversation->id
        );

        $listener = new ConversationLinkedToClientListener();
        $listener->handle($event);

        $metadata = ConversationBillingMetadata::where('client_conversation_id', $clientConversation->id)->first();

        $this->assertNotNull($metadata);
        $this->assertEquals($clientConversation->id, $metadata->client_conversation_id);
    }

    /**
     * Test ad-hoc category is default for clients without plans.
     */
    public function test_ad_hoc_category_is_default(): void
    {
        $conversationId = $this->createConversation();
        $clientConversation = $this->createClientConversation($this->client->id, $conversationId);

        $event = new ConversationLinkedToClient(
            conversationId: $conversationId,
            clientId: $this->client->id,
            clientConversationId: $clientConversation->id
        );

        $listener = new ConversationLinkedToClientListener();
        $listener->handle($event);

        $metadata = ConversationBillingMetadata::where('client_conversation_id', $clientConversation->id)->first();

        $this->assertEquals(ConversationBillingMetadata::CATEGORY_AD_HOC, $metadata->billing_category);
        $this->assertTrue($metadata->is_billable);
    }

    /**
     * Test included category for clients with service plans.
     */
    public function test_included_category_for_service_plan_clients(): void
    {
        // Create service plan for client
        BillingTemplate::create([
            'client_id' => $this->client->id,
            'name' => 'Test Template',
            'product_type' => 'subscription_service_plan',
            'status' => 'active',
            'product_config' => [],
        ]);

        $conversationId = $this->createConversation();
        $clientConversation = $this->createClientConversation($this->client->id, $conversationId);

        $event = new ConversationLinkedToClient(
            conversationId: $conversationId,
            clientId: $this->client->id,
            clientConversationId: $clientConversation->id
        );

        $listener = new ConversationLinkedToClientListener();
        $listener->handle($event);

        $metadata = ConversationBillingMetadata::where('client_conversation_id', $clientConversation->id)->first();

        $this->assertEquals(ConversationBillingMetadata::CATEGORY_INCLUDED, $metadata->billing_category);
        $this->assertFalse($metadata->is_billable);
    }

    /**
     * Test mark as billable method.
     */
    public function test_mark_as_billable(): void
    {
        $conversationId = $this->createConversation();
        $clientConversation = $this->createClientConversation($this->client->id, $conversationId);

        $metadata = ConversationBillingMetadata::create([
            'client_conversation_id' => $clientConversation->id,
            'billing_category' => ConversationBillingMetadata::CATEGORY_INCLUDED,
            'is_billable' => false,
        ]);

        $metadata->markAsBillable(ConversationBillingMetadata::CATEGORY_AD_HOC);

        $this->assertTrue($metadata->fresh()->is_billable);
        $this->assertEquals(ConversationBillingMetadata::CATEGORY_AD_HOC, $metadata->fresh()->billing_category);
    }

    /**
     * Test add billable time method.
     */
    public function test_add_billable_time(): void
    {
        $conversationId = $this->createConversation();
        $clientConversation = $this->createClientConversation($this->client->id, $conversationId);

        $metadata = ConversationBillingMetadata::create([
            'client_conversation_id' => $clientConversation->id,
            'billing_category' => ConversationBillingMetadata::CATEGORY_AD_HOC,
            'is_billable' => true,
            'billable_time_minutes' => 0,
        ]);

        $metadata->addBillableTime(30);
        $this->assertEquals(30, $metadata->fresh()->billable_time_minutes);

        $metadata->addBillableTime(15);
        $this->assertEquals(45, $metadata->fresh()->billable_time_minutes);
    }

    /**
     * Test mark as invoiced method.
     */
    public function test_mark_as_invoiced(): void
    {
        $conversationId = $this->createConversation();
        $clientConversation = $this->createClientConversation($this->client->id, $conversationId);

        $metadata = ConversationBillingMetadata::create([
            'client_conversation_id' => $clientConversation->id,
            'billing_category' => ConversationBillingMetadata::CATEGORY_AD_HOC,
            'is_billable' => true,
        ]);

        $metadata->markAsInvoiced(123);

        $this->assertEquals(123, $metadata->fresh()->invoice_id);
    }

    /**
     * Test dynamic relationship from client conversation to billing metadata.
     */
    public function test_dynamic_relationship_from_client_conversation(): void
    {
        // Register the dynamic relationship
        ClientConversation::resolveRelationUsing('billingMetadata', function ($clientConversation) {
            return $clientConversation->hasOne(ConversationBillingMetadata::class, 'client_conversation_id');
        });

        $conversationId = $this->createConversation();
        $clientConversation = $this->createClientConversation($this->client->id, $conversationId);

        $metadata = ConversationBillingMetadata::create([
            'client_conversation_id' => $clientConversation->id,
            'billing_category' => ConversationBillingMetadata::CATEGORY_AD_HOC,
            'is_billable' => true,
        ]);

        $loaded = $clientConversation->fresh()->billingMetadata;

        $this->assertNotNull($loaded);
        $this->assertEquals($metadata->id, $loaded->id);
    }

    /**
     * Test scope billable.
     */
    public function test_scope_billable(): void
    {
        $conversationId1 = $this->createConversation();
        $clientConversation1 = $this->createClientConversation($this->client->id, $conversationId1);

        $conversationId2 = $this->createConversation();
        $clientConversation2 = $this->createClientConversation($this->client->id, $conversationId2);

        ConversationBillingMetadata::create([
            'client_conversation_id' => $clientConversation1->id,
            'billing_category' => ConversationBillingMetadata::CATEGORY_AD_HOC,
            'is_billable' => true,
        ]);

        ConversationBillingMetadata::create([
            'client_conversation_id' => $clientConversation2->id,
            'billing_category' => ConversationBillingMetadata::CATEGORY_INCLUDED,
            'is_billable' => false,
        ]);

        $billable = ConversationBillingMetadata::billable()->get();

        $this->assertCount(1, $billable);
        $this->assertEquals($clientConversation1->id, $billable->first()->client_conversation_id);
    }

    /**
     * Test scope by category.
     */
    public function test_scope_by_category(): void
    {
        $conversationId1 = $this->createConversation();
        $clientConversation1 = $this->createClientConversation($this->client->id, $conversationId1);

        $conversationId2 = $this->createConversation();
        $clientConversation2 = $this->createClientConversation($this->client->id, $conversationId2);

        ConversationBillingMetadata::create([
            'client_conversation_id' => $clientConversation1->id,
            'billing_category' => ConversationBillingMetadata::CATEGORY_AD_HOC,
            'is_billable' => true,
        ]);

        ConversationBillingMetadata::create([
            'client_conversation_id' => $clientConversation2->id,
            'billing_category' => ConversationBillingMetadata::CATEGORY_PROJECT,
            'is_billable' => true,
        ]);

        $adHoc = ConversationBillingMetadata::category(ConversationBillingMetadata::CATEGORY_AD_HOC)->get();

        $this->assertCount(1, $adHoc);
        $this->assertEquals($clientConversation1->id, $adHoc->first()->client_conversation_id);
    }
}
