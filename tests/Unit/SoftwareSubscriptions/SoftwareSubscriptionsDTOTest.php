<?php

declare(strict_types=1);

namespace Tests\Unit\SoftwareSubscriptions;

use Modules\SoftwareSubscriptions\DataTransferObjects\SoftwareAssignmentAddedData;
use Modules\SoftwareSubscriptions\DataTransferObjects\SoftwareAssignmentRevokedData;
use Modules\SoftwareSubscriptions\DataTransferObjects\SoftwareCountChangedData;
use Modules\SoftwareSubscriptions\DataTransferObjects\SoftwareDeploymentCompletedData;
use Modules\SoftwareSubscriptions\DataTransferObjects\SoftwareDeploymentFailedData;
use Tests\PureUnitTestCase;

final class SoftwareSubscriptionsDTOTest extends PureUnitTestCase
{
    // ─── SoftwareAssignmentAddedData ──────────────────────────────────────────

    public function test_assignment_added_constructor_assigns_properties(): void
    {
        $dto = new SoftwareAssignmentAddedData(
            assignmentId: 1,
            subscriptionId: 2,
            clientId: 3,
            assignableType: 'User',
            assignableId: 4,
            assignableName: 'John Doe',
            newAssignedCount: 5,
            purchasedQuantity: 10,
            licenseKey: 'KEY-123',
        );

        $this->assertSame(1, $dto->assignmentId);
        $this->assertSame(2, $dto->subscriptionId);
        $this->assertSame('KEY-123', $dto->licenseKey);
    }

    public function test_assignment_added_to_array_has_required_keys(): void
    {
        $dto = new SoftwareAssignmentAddedData(
            assignmentId: 1,
            subscriptionId: 2,
            clientId: 3,
            assignableType: 'Asset',
            assignableId: 5,
            assignableName: null,
            newAssignedCount: 3,
            purchasedQuantity: 5,
        );

        $arr = $dto->toArray();

        $this->assertArrayHasKey('assignment_id', $arr);
        $this->assertArrayHasKey('subscription_id', $arr);
        $this->assertArrayHasKey('assignable_type', $arr);
        $this->assertArrayHasKey('new_assigned_count', $arr);
        $this->assertNull($arr['license_key']);
    }

    // ─── SoftwareAssignmentRevokedData ────────────────────────────────────────

    public function test_assignment_revoked_constructor_assigns_properties(): void
    {
        $dto = new SoftwareAssignmentRevokedData(
            assignmentId: 10,
            subscriptionId: 20,
            clientId: 30,
            assignableType: 'Contact',
            assignableId: 40,
            assignableName: 'Jane',
            revocationReason: 'user_deactivated',
            newAssignedCount: 2,
            purchasedQuantity: 5,
        );

        $this->assertSame(10, $dto->assignmentId);
        $this->assertSame('user_deactivated', $dto->revocationReason);
        $this->assertNull($dto->assignableEmail);
        $this->assertNull($dto->productName);
        $this->assertNull($dto->revokedAt);
    }

    public function test_assignment_revoked_to_array_has_expected_keys(): void
    {
        $dt = new \DateTime('2025-01-15');
        $dto = new SoftwareAssignmentRevokedData(
            assignmentId: 10,
            subscriptionId: 20,
            clientId: 30,
            assignableType: 'Contact',
            assignableId: 40,
            assignableName: 'Jane',
            revocationReason: 'manual',
            newAssignedCount: 0,
            purchasedQuantity: 5,
            assignableEmail: 'jane@example.com',
            productName: 'Office',
            revokedAt: $dt,
        );

        $arr = $dto->toArray();

        $this->assertSame('jane@example.com', $arr['assignable_email']);
        $this->assertSame('Office', $arr['product_name']);
        $this->assertSame($dt, $arr['revoked_at']);
    }

    // ─── SoftwareCountChangedData ─────────────────────────────────────────────

    public function test_count_changed_constructor_assigns_properties(): void
    {
        $dto = new SoftwareCountChangedData(
            subscriptionId: 1,
            clientId: 2,
            softwareProductId: 3,
            productName: 'Adobe CC',
            previousCount: 5,
            newCount: 8,
            changeReason: 'assignment_added',
            relatedAssignmentId: 99,
        );

        $this->assertSame(1, $dto->subscriptionId);
        $this->assertSame('Adobe CC', $dto->productName);
        $this->assertSame(5, $dto->previousCount);
        $this->assertSame(8, $dto->newCount);
        $this->assertSame(99, $dto->relatedAssignmentId);
    }

    public function test_count_changed_get_delta_positive(): void
    {
        $dto = new SoftwareCountChangedData(
            subscriptionId: 1,
            clientId: 2,
            softwareProductId: 3,
            productName: 'App',
            previousCount: 5,
            newCount: 8,
            changeReason: 'assignment_added',
        );

        $this->assertSame(3, $dto->getDelta());
    }

    public function test_count_changed_get_delta_negative(): void
    {
        $dto = new SoftwareCountChangedData(
            subscriptionId: 1,
            clientId: 2,
            softwareProductId: 3,
            productName: 'App',
            previousCount: 10,
            newCount: 7,
            changeReason: 'assignment_revoked',
        );

        $this->assertSame(-3, $dto->getDelta());
    }

    public function test_count_changed_get_delta_zero(): void
    {
        $dto = new SoftwareCountChangedData(
            subscriptionId: 1,
            clientId: 2,
            softwareProductId: 3,
            productName: 'App',
            previousCount: 5,
            newCount: 5,
            changeReason: 'no_change',
        );

        $this->assertSame(0, $dto->getDelta());
    }

    public function test_count_changed_to_array_has_required_keys(): void
    {
        $dto = new SoftwareCountChangedData(1, 2, 3, 'App', 5, 8, 'added');
        $arr = $dto->toArray();

        $this->assertArrayHasKey('subscription_id', $arr);
        $this->assertArrayHasKey('product_name', $arr);
        $this->assertArrayHasKey('previous_count', $arr);
        $this->assertArrayHasKey('new_count', $arr);
        $this->assertNull($arr['related_assignment_id']);
    }

    // ─── SoftwareDeploymentCompletedData ─────────────────────────────────────

    public function test_deployment_completed_has_correct_properties(): void
    {
        $dto = new SoftwareDeploymentCompletedData(
            assignmentId: 1,
            subscriptionId: 2,
            clientId: 3,
            assignableType: 'Asset',
            assignableId: 4,
            assignableName: 'Laptop-01',
            productName: 'Zoom',
            deploymentDetails: ['version' => '5.15'],
        );

        $this->assertSame(1, $dto->assignmentId);
        $this->assertSame('Zoom', $dto->productName);
        $this->assertSame(['version' => '5.15'], $dto->deploymentDetails);
    }

    public function test_deployment_completed_to_array_has_required_keys(): void
    {
        $dto = new SoftwareDeploymentCompletedData(
            assignmentId: 1,
            subscriptionId: 2,
            clientId: 3,
            assignableType: 'Asset',
            assignableId: 4,
            assignableName: null,
            productName: 'Zoom',
        );

        $arr = $dto->toArray();
        $this->assertArrayHasKey('deployment_details', $arr);
        $this->assertSame([], $arr['deployment_details']);
    }

    // ─── SoftwareDeploymentFailedData ─────────────────────────────────────────

    public function test_deployment_failed_has_correct_properties(): void
    {
        $dto = new SoftwareDeploymentFailedData(
            assignmentId: 5,
            subscriptionId: 6,
            clientId: 7,
            assignableType: 'Asset',
            assignableId: 8,
            assignableName: 'PC-01',
            productName: 'Teams',
            errorMessage: 'Connection refused',
            failureDetails: ['code' => 500],
            attemptNumber: 2,
        );

        $this->assertSame(5, $dto->assignmentId);
        $this->assertSame('Connection refused', $dto->errorMessage);
        $this->assertSame(2, $dto->attemptNumber);
    }

    public function test_deployment_failed_to_array_has_required_keys(): void
    {
        $dto = new SoftwareDeploymentFailedData(
            assignmentId: 5,
            subscriptionId: 6,
            clientId: 7,
            assignableType: 'Asset',
            assignableId: 8,
            assignableName: null,
            productName: 'Teams',
            errorMessage: 'Timeout',
        );

        $arr = $dto->toArray();
        $this->assertArrayHasKey('error_message', $arr);
        $this->assertSame(1, $arr['attempt_number']);
        $this->assertSame([], $arr['failure_details']);
    }
}
