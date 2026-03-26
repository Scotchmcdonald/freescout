<?php

declare(strict_types=1);

namespace Tests\Unit\GoogleAdmin;

use Modules\GoogleAdmin\DataTransferObjects\GoogleLicenseDiscoveredData;
use Tests\PureUnitTestCase;

final class GoogleLicenseDiscoveredDataTest extends PureUnitTestCase
{
    // ─── constructor ──────────────────────────────────────────────────────────

    public function test_constructor_assigns_all_properties(): void
    {
        $dto = new GoogleLicenseDiscoveredData(
            userEmail: 'user@example.com',
            productId: 'Google-Apps',
            productName: 'Google Workspace',
            skuId: '1010020027',
            skuName: 'Business Plus',
            assignedAt: '2025-01-15T00:00:00Z',
            contactId: 5,
            clientId: 3,
            metadata: ['region' => 'us'],
        );

        $this->assertSame('user@example.com', $dto->userEmail);
        $this->assertSame('Google-Apps', $dto->productId);
        $this->assertSame('Google Workspace', $dto->productName);
        $this->assertSame('1010020027', $dto->skuId);
        $this->assertSame('Business Plus', $dto->skuName);
        $this->assertSame('2025-01-15T00:00:00Z', $dto->assignedAt);
        $this->assertSame(5, $dto->contactId);
        $this->assertSame(3, $dto->clientId);
        $this->assertSame(['region' => 'us'], $dto->metadata);
    }

    // ─── fromApiResponse ─────────────────────────────────────────────────────

    public function test_from_api_response_maps_standard_keys(): void
    {
        $dto = GoogleLicenseDiscoveredData::fromApiResponse(
            license: [
                'userId' => 'admin@company.com',
                'productId' => 'Google-Apps',
                'skuId' => '1010020027',
                'skuName' => 'Workspace Business Plus',
                'assignedAt' => '2025-06-01T12:00:00Z',
                'contact_id' => 10,
                'client_id' => 2,
            ],
            productInfo: ['productName' => 'Google Workspace Business Plus']
        );

        $this->assertSame('admin@company.com', $dto->userEmail);
        $this->assertSame('Google-Apps', $dto->productId);
        $this->assertSame('Google Workspace Business Plus', $dto->productName);
        $this->assertSame('1010020027', $dto->skuId);
        $this->assertSame(10, $dto->contactId);
        $this->assertSame(2, $dto->clientId);
    }

    public function test_from_api_response_falls_back_to_user_email_key(): void
    {
        $dto = GoogleLicenseDiscoveredData::fromApiResponse([
            'userEmail' => 'fallback@company.com',
            'productId' => 'Google-Apps',
            'skuId' => 'sku-1',
        ]);

        $this->assertSame('fallback@company.com', $dto->userEmail);
    }

    public function test_from_api_response_resolves_product_name_from_product_id(): void
    {
        // When productInfo is empty, should resolve from productId
        $dto = GoogleLicenseDiscoveredData::fromApiResponse([
            'userId' => 'user@test.com',
            'productId' => 'Google-Apps',
            'skuId' => 'x',
        ]);

        $this->assertSame('Google Workspace', $dto->productName);
    }

    public function test_from_api_response_resolves_google_vault_product(): void
    {
        $dto = GoogleLicenseDiscoveredData::fromApiResponse([
            'userId' => 'user@test.com',
            'productId' => 'Google-Vault',
            'skuId' => 'x',
        ]);

        $this->assertSame('Google Vault', $dto->productName);
    }

    public function test_from_api_response_unknown_product_id_returns_raw_id(): void
    {
        $dto = GoogleLicenseDiscoveredData::fromApiResponse([
            'userId' => 'user@test.com',
            'productId' => 'unknown-product-xyz',
            'skuId' => 'x',
        ]);

        $this->assertSame('unknown-product-xyz', $dto->productName);
    }

    public function test_from_api_response_empty_payload_uses_defaults(): void
    {
        $dto = GoogleLicenseDiscoveredData::fromApiResponse([]);

        $this->assertSame('', $dto->userEmail);
        $this->assertSame('', $dto->productId);
        $this->assertNull($dto->assignedAt);
        $this->assertNull($dto->contactId);
        $this->assertNull($dto->clientId);
    }

    // ─── toArray ──────────────────────────────────────────────────────────────

    public function test_to_array_returns_all_required_keys(): void
    {
        $dto = new GoogleLicenseDiscoveredData(
            userEmail: 'u@e.com',
            productId: 'pid',
            productName: 'pname',
            skuId: 'sid',
            skuName: 'sname',
            assignedAt: null,
            contactId: null,
            clientId: null,
        );

        $arr = $dto->toArray();

        foreach (['user_email', 'product_id', 'product_name', 'sku_id', 'sku_name', 'assigned_at', 'contact_id', 'client_id', 'metadata'] as $key) {
            $this->assertArrayHasKey($key, $arr, "Missing key: $key");
        }
    }

    public function test_to_array_values_match_properties(): void
    {
        $dto = new GoogleLicenseDiscoveredData(
            userEmail: 'ceo@corp.com',
            productId: '101036',
            productName: 'Google Workspace Business Plus',
            skuId: '1010020027',
            skuName: 'Business Plus',
            assignedAt: '2025-01-01',
            contactId: 99,
            clientId: 8,
        );

        $arr = $dto->toArray();

        $this->assertSame('ceo@corp.com', $arr['user_email']);
        $this->assertSame('101036', $arr['product_id']);
        $this->assertSame(99, $arr['contact_id']);
        $this->assertSame(8, $arr['client_id']);
    }
}
