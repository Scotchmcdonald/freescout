<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\ClientPolicy;
use Mockery;
use Modules\Crm\Models\Client;
use Modules\Crm\Models\Company;
use Tests\PureUnitTestCase;

class ClientPolicyTest extends PureUnitTestCase
{
    private function makeUser(
        int $id,
        bool $isClient = false,
        bool $isActive = true,
        ?int $companyId = null,
        array $permissions = [],
        array $accessibleCompanyIds = []
    ): User {
        /** @var User&\Mockery\MockInterface $user */
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = $id;
        $company = null;
        if ($companyId !== null) {
            $company = new Company;
            $company->id = $companyId;
        }

        $user->shouldReceive('isClient')->andReturn($isClient);
        $user->shouldReceive('isActive')->andReturn($isActive);
        $user->shouldReceive('company')->andReturn($company);
        $user->shouldReceive('hasPermission')
            ->andReturnUsing(static fn (string $permission): bool => in_array($permission, $permissions, true));
        $user->shouldReceive('hasCompanyAccess')
            ->andReturnUsing(static fn (int $companyId): bool => in_array($companyId, $accessibleCompanyIds, true));

        return $user;
    }

    private function makeClient(int $id, ?int $companyId = null, bool $isActive = true): Client
    {
        /** @var Client&\Mockery\MockInterface $client */
        $client = Mockery::mock(Client::class)->makePartial();
        $client->id = $id;
        $client->company_id = $companyId;
        $client->shouldReceive('isActive')->andReturn($isActive);

        return $client;
    }

    public function test_staff_with_manage_crm_can_view_any_client(): void
    {
        $policy = new ClientPolicy;
        $user = $this->makeUser(1, permissions: ['manage_crm']);

        $this->assertFalse($policy->viewAny($user));
        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->update($user, $this->makeClient(10, 200)));
        $this->assertTrue($policy->delete($user, $this->makeClient(10, 200)));
    }

    public function test_staff_with_view_crm_can_view_accessible_company_client_only(): void
    {
        $policy = new ClientPolicy;
        $user = $this->makeUser(1, permissions: ['view_crm'], accessibleCompanyIds: [200]);
        $accessibleClient = $this->makeClient(10, 200);
        $otherCompanyClient = $this->makeClient(11, 201);
        $orphanClient = $this->makeClient(12, null);

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $accessibleClient));
        $this->assertFalse($policy->view($user, $otherCompanyClient));
        $this->assertFalse($policy->view($user, $orphanClient));
        $this->assertTrue($policy->viewPortal($user, $accessibleClient));
        $this->assertFalse($policy->managePayments($user, $accessibleClient));
    }

    public function test_internal_user_without_crm_permissions_requires_company_access(): void
    {
        $policy = new ClientPolicy;
        $user = $this->makeUser(1, accessibleCompanyIds: [300]);
        $client = $this->makeClient(30, 300);
        $otherClient = $this->makeClient(31, 301);

        $this->assertFalse($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $client));
        $this->assertFalse($policy->view($user, $otherClient));
        $this->assertFalse($policy->create($user));
    }

    public function test_external_user_can_only_view_active_own_company_client(): void
    {
        $policy = new ClientPolicy;
        $user = $this->makeUser(5, isClient: true, isActive: true, companyId: 42);
        $ownActiveClient = $this->makeClient(42, 900, true);
        $ownInactiveClient = $this->makeClient(42, 900, false);
        $otherClient = $this->makeClient(43, 900, true);

        $this->assertFalse($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $ownActiveClient));
        $this->assertFalse($policy->view($user, $otherClient));
        $this->assertTrue($policy->viewPortal($user, $ownActiveClient));
        $this->assertFalse($policy->viewPortal($user, $ownInactiveClient));
        $this->assertTrue($policy->viewInvoices($user, $ownActiveClient));
        $this->assertFalse($policy->managePayments($user, $ownInactiveClient));
    }

    public function test_inactive_external_user_is_denied_portal_access_and_payments(): void
    {
        $policy = new ClientPolicy;
        $user = $this->makeUser(5, isClient: true, isActive: false, companyId: 42);
        $client = $this->makeClient(42, 900, true);

        $this->assertFalse($policy->view($user, $client));
        $this->assertFalse($policy->viewPortal($user, $client));
        $this->assertFalse($policy->viewAssets($user, $client));
        $this->assertFalse($policy->viewSubscriptions($user, $client));
        $this->assertFalse($policy->managePayments($user, $client));
    }
}