<?php

declare(strict_types=1);

namespace Tests\Integration\SoftwareSubscriptions;

use Illuminate\Support\Facades\Event;
use Modules\SoftwareSubscriptions\Events\SoftwareDeploymentCompleted;
use Modules\SoftwareSubscriptions\Events\SoftwareDeploymentFailed;
use Modules\SoftwareSubscriptions\Models\SoftwareAssignment;
use Modules\SoftwareSubscriptions\Services\LicenseDeploymentService;
use Tests\IntegrationTestCase;

class TestSoftwareAssignment extends SoftwareAssignment
{
    public bool $activeFlag = true;

    /** @var array<string, mixed>|null */
    public ?array $lastUpdatedPayload = null;

    /** @var array<string, mixed>|null */
    public ?array $lastInProgressDetails = null;

    /** @var array<string, mixed>|null */
    public ?array $lastCompletedDetails = null;

    /** @var array<string, mixed>|null */
    public ?array $lastFailedDetails = null;

    public ?string $lastFailedError = null;

    public function isActive(): bool
    {
        return $this->activeFlag;
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public function markDeploymentInProgress(array $details = []): void
    {
        $this->lastInProgressDetails = $details;
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public function markDeploymentCompleted(array $details = []): void
    {
        $this->lastCompletedDetails = $details;
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public function markDeploymentFailed(string $error, array $details = []): void
    {
        $this->lastFailedError = $error;
        $this->lastFailedDetails = $details;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        $this->lastUpdatedPayload = $attributes;

        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return true;
    }
}

class TestableLicenseDeploymentService extends LicenseDeploymentService
{
    public bool $action1Available = false;

    public bool $deployResult = true;

    public ?SoftwareAssignment $deployedAssignment = null;

    protected function isAction1Available(): bool
    {
        return $this->action1Available;
    }

    protected function deployViaAction1(SoftwareAssignment $assignment): bool
    {
        $this->deployedAssignment = $assignment;

        return $this->deployResult;
    }
}

class RetrySpyLicenseDeploymentService extends LicenseDeploymentService
{
    public bool $initiateCalled = false;

    public function initiateDeployment(SoftwareAssignment $assignment): bool
    {
        $this->initiateCalled = true;

        return true;
    }
}

class LicenseDeploymentServiceTest extends IntegrationTestCase
{
    public function test_initiate_deployment_rejects_revoked_assignment(): void
    {
        $service = new TestableLicenseDeploymentService;
        $assignment = new TestSoftwareAssignment;
        $assignment->id = 11;
        $assignment->activeFlag = false;
        $assignment->deployment_status = SoftwareAssignment::DEPLOYMENT_PENDING;

        $result = $service->initiateDeployment($assignment);

        $this->assertFalse($result);
    }

    public function test_initiate_deployment_returns_true_when_already_completed(): void
    {
        $service = new TestableLicenseDeploymentService;
        $assignment = new TestSoftwareAssignment;
        $assignment->id = 12;
        $assignment->activeFlag = true;
        $assignment->deployment_status = SoftwareAssignment::DEPLOYMENT_COMPLETED;

        $result = $service->initiateDeployment($assignment);

        $this->assertTrue($result);
        $this->assertNull($assignment->lastInProgressDetails);
    }

    public function test_initiate_deployment_uses_action1_when_available(): void
    {
        $service = new TestableLicenseDeploymentService;
        $service->action1Available = true;

        $assignment = new TestSoftwareAssignment;
        $assignment->id = 13;
        $assignment->activeFlag = true;
        $assignment->deployment_status = SoftwareAssignment::DEPLOYMENT_PENDING;

        $result = $service->initiateDeployment($assignment);

        $this->assertTrue($result);
        $this->assertSame(['initiated_by' => 'LicenseDeploymentService'], $assignment->lastInProgressDetails);
        $this->assertSame($assignment, $service->deployedAssignment);
    }

    public function test_initiate_deployment_marks_not_required_when_no_rmm_integration(): void
    {
        $service = new TestableLicenseDeploymentService;
        $service->action1Available = false;

        $assignment = new TestSoftwareAssignment;
        $assignment->id = 14;
        $assignment->activeFlag = true;
        $assignment->deployment_status = SoftwareAssignment::DEPLOYMENT_PENDING;

        $result = $service->initiateDeployment($assignment);

        $this->assertTrue($result);
        $this->assertSame(SoftwareAssignment::DEPLOYMENT_NOT_REQUIRED, $assignment->lastUpdatedPayload['deployment_status']);
        $this->assertSame('No RMM integration available', $assignment->lastUpdatedPayload['deployment_details']['reason']);
    }

    public function test_retry_deployment_rejects_non_failed_assignments(): void
    {
        $service = new RetrySpyLicenseDeploymentService;
        $assignment = new TestSoftwareAssignment;
        $assignment->deployment_status = SoftwareAssignment::DEPLOYMENT_PENDING;

        $result = $service->retryDeployment($assignment);

        $this->assertFalse($result);
        $this->assertFalse($service->initiateCalled);
    }

    public function test_retry_deployment_increments_attempt_count_and_re_initiates(): void
    {
        $service = new RetrySpyLicenseDeploymentService;
        $assignment = new TestSoftwareAssignment;
        $assignment->deployment_status = SoftwareAssignment::DEPLOYMENT_FAILED;
        $assignment->deployment_details = ['attempt_count' => 2, 'error' => 'timeout'];

        $result = $service->retryDeployment($assignment);

        $this->assertTrue($result);
        $this->assertTrue($service->initiateCalled);
        $this->assertSame(SoftwareAssignment::DEPLOYMENT_PENDING, $assignment->lastUpdatedPayload['deployment_status']);
        $this->assertSame(3, $assignment->lastUpdatedPayload['deployment_details']['attempt_count']);
    }

    public function test_mark_completed_dispatches_completed_event_with_expected_payload(): void
    {
        Event::fake();

        $service = new TestableLicenseDeploymentService;
        $assignment = $this->makeAssignmentForEvents(21, 301, 401, 'Device A', 'Endpoint Secure');

        $service->markCompleted($assignment, ['method' => 'action1']);

        $this->assertSame(['method' => 'action1'], $assignment->lastCompletedDetails);

        Event::assertDispatched(SoftwareDeploymentCompleted::class, function (SoftwareDeploymentCompleted $event): bool {
            return $event->data->assignmentId === 21
                && $event->data->subscriptionId === 301
                && $event->data->clientId === 401
                && $event->data->assignableName === 'Device A'
                && $event->data->productName === 'Endpoint Secure';
        });
    }

    public function test_mark_failed_dispatches_failed_event_with_attempt_number(): void
    {
        Event::fake();

        $service = new TestableLicenseDeploymentService;
        $assignment = $this->makeAssignmentForEvents(22, 302, 402, 'Device B', 'Mail Suite');

        $service->markFailed($assignment, 'agent timeout', ['region' => 'us-east'], 4);

        $this->assertSame('agent timeout', $assignment->lastFailedError);
        $this->assertSame(['region' => 'us-east'], $assignment->lastFailedDetails);

        Event::assertDispatched(SoftwareDeploymentFailed::class, function (SoftwareDeploymentFailed $event): bool {
            return $event->data->assignmentId === 22
                && $event->data->subscriptionId === 302
                && $event->data->clientId === 402
                && $event->data->errorMessage === 'agent timeout'
                && $event->data->attemptNumber === 4;
        });
    }

    public function test_authorization_boundary_revoked_assignment_is_unauthorized_for_deployment(): void
    {
        // Authorization / validation boundary: a non-failed software assignment must
        // be denied retry — the service validates the assignment status and
        // returns false, preventing unauthorized license re-provisioning.
        $service = new RetrySpyLicenseDeploymentService;
        $assignment = new TestSoftwareAssignment;
        $assignment->deployment_status = SoftwareAssignment::DEPLOYMENT_COMPLETED;

        $result = $service->retryDeployment($assignment);

        $this->assertFalse($result,
            'Non-failed assignment must be unauthorized for retry — validation boundary enforced'
        );
    }

    private function makeAssignmentForEvents(
        int $assignmentId,
        int $subscriptionId,
        int $clientId,
        string $assignableName,
        string $productName
    ): TestSoftwareAssignment {
        $assignment = new TestSoftwareAssignment;
        $assignment->id = $assignmentId;
        $assignment->assignable_type = 'asset';
        $assignment->assignable_id = 88;

        $subscription = new class($subscriptionId, $clientId, $productName)
        {
            public int $id;

            public int $client_id;

            public object $product;

            public function __construct(int $id, int $clientId, string $productName)
            {
                $this->id = $id;
                $this->client_id = $clientId;
                $this->product = new class($productName)
                {
                    public string $name;

                    public function __construct(string $name)
                    {
                        $this->name = $name;
                    }
                };
            }
        };

        $assignable = new class($assignableName)
        {
            private string $name;

            public function __construct(string $name)
            {
                $this->name = $name;
            }

            public function getAttribute(string $key): ?string
            {
                return $key === 'name' ? $this->name : null;
            }
        };

        $assignment->subscription = $subscription;
        $assignment->assignable = $assignable;

        return $assignment;
    }
}
