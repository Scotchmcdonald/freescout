<?php

declare(strict_types=1);

namespace Tests\Unit\Crm;

use Modules\Crm\Models\Client;
use Tests\PureUnitTestCase;

if (! class_exists(StubCrmClient::class)) {
    final class StubCrmClient extends Client
    {
        protected static function booted(): void {}

        protected function casts(): array
        {
            return [];
        }
    }
}

final class ClientIsActiveTest extends PureUnitTestCase
{
    public function test_is_active_returns_true_when_status_is_active(): void
    {
        $client = new StubCrmClient;
        $client->status = 'active';

        $this->assertTrue($client->isActive());
    }

    public function test_is_active_returns_false_when_status_is_inactive(): void
    {
        $client = new StubCrmClient;
        $client->status = 'inactive';

        $this->assertFalse($client->isActive());
    }

    public function test_is_active_returns_false_when_status_is_suspended(): void
    {
        $client = new StubCrmClient;
        $client->status = 'suspended';

        $this->assertFalse($client->isActive());
    }

    public function test_is_active_is_case_sensitive(): void
    {
        $client = new StubCrmClient;
        $client->status = 'Active';   // uppercase A

        $this->assertFalse($client->isActive());
    }

    public function test_archived_client_authorization_gate_is_inactive(): void
    {
        $client = new StubCrmClient;
        $client->status = 'archived';

        // Authorization boundary: archived clients must fail the isActive() check
        // so that features guarded by isActive() correctly deny access
        $this->assertFalse($client->isActive());
    }

    public function test_only_active_status_passes_authorization_check(): void
    {
        $deniedStatuses = ['inactive', 'archived', 'suspended', 'pending', 'deleted', ''];

        foreach ($deniedStatuses as $status) {
            $client = new StubCrmClient;
            $client->status = $status;

            $this->assertFalse(
                $client->isActive(),
                "Status '$status' should not pass the authorization boundary check"
            );
        }
    }
}
