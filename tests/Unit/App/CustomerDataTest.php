<?php

declare(strict_types=1);

namespace Tests\Unit\App;

use App\DataTransferObjects\CustomerData;
use Tests\PureUnitTestCase;

final class CustomerDataTest extends PureUnitTestCase
{
    // ─── constructor ──────────────────────────────────────────────────────────

    public function test_constructor_with_required_field_only(): void
    {
        $dto = new CustomerData(firstName: 'John');

        $this->assertSame('John', $dto->firstName);
        $this->assertNull($dto->lastName);
        $this->assertNull($dto->email);
        $this->assertNull($dto->company);
    }

    public function test_constructor_assigns_all_fields(): void
    {
        $dto = new CustomerData(
            firstName: 'Jane',
            lastName: 'Doe',
            email: 'jane@example.com',
            company: 'Acme Corp',
            jobTitle: 'CEO',
            phone: '+1-555-0100',
            timezone: 'America/New_York',
            address: '123 Main St',
            city: 'Springfield',
            state: 'IL',
            zip: '62701',
            country: 'US',
            notes: 'VIP customer',
        );

        $this->assertSame('Jane', $dto->firstName);
        $this->assertSame('Doe', $dto->lastName);
        $this->assertSame('jane@example.com', $dto->email);
        $this->assertSame('Acme Corp', $dto->company);
        $this->assertSame('CEO', $dto->jobTitle);
        $this->assertSame('+1-555-0100', $dto->phone);
        $this->assertSame('America/New_York', $dto->timezone);
        $this->assertSame('123 Main St', $dto->address);
        $this->assertSame('Springfield', $dto->city);
        $this->assertSame('IL', $dto->state);
        $this->assertSame('62701', $dto->zip);
        $this->assertSame('US', $dto->country);
        $this->assertSame('VIP customer', $dto->notes);
    }

    // ─── getFullName ──────────────────────────────────────────────────────────

    public function test_get_full_name_with_first_and_last(): void
    {
        $dto = new CustomerData(firstName: 'John', lastName: 'Smith');
        $this->assertSame('John Smith', $dto->getFullName());
    }

    public function test_get_full_name_with_first_only(): void
    {
        $dto = new CustomerData(firstName: 'Alice');
        $this->assertSame('Alice', $dto->getFullName());
    }

    public function test_get_full_name_trims_whitespace(): void
    {
        $dto = new CustomerData(firstName: 'Bob', lastName: null);
        $this->assertSame('Bob', $dto->getFullName());
    }

    // ─── fromArray ────────────────────────────────────────────────────────────

    public function test_from_array_maps_standard_keys(): void
    {
        $dto = CustomerData::fromArray([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'company' => 'Acme',
            'phone' => '555-1234',
        ]);

        $this->assertSame('Jane', $dto->firstName);
        $this->assertSame('Doe', $dto->lastName);
        $this->assertSame('jane@example.com', $dto->email);
        $this->assertSame('Acme', $dto->company);
        $this->assertSame('555-1234', $dto->phone);
    }

    public function test_from_array_returns_empty_string_first_name_when_missing(): void
    {
        $dto = CustomerData::fromArray([]);
        $this->assertSame('', $dto->firstName);
    }

    public function test_from_array_handles_non_string_first_name(): void
    {
        $dto = CustomerData::fromArray(['first_name' => ['array_value']]);
        $this->assertSame('', $dto->firstName);
    }

    public function test_from_array_maps_nullable_fields_to_null_when_absent(): void
    {
        $dto = CustomerData::fromArray(['first_name' => 'Only']);
        $this->assertNull($dto->lastName);
        $this->assertNull($dto->email);
        $this->assertNull($dto->company);
        $this->assertNull($dto->phone);
    }

    public function test_from_array_handles_emails_array(): void
    {
        $dto = CustomerData::fromArray([
            'first_name' => 'Test',
            'emails' => [
                ['email' => 'work@example.com', 'type' => 'work'],
                ['email' => 'home@example.com', 'type' => 'home'],
            ],
        ]);

        $this->assertIsArray($dto->emails);
        $this->assertCount(2, $dto->emails);
        $this->assertSame('work@example.com', $dto->emails[0]['email']);
        $this->assertSame('work', $dto->emails[0]['type']);
    }

    public function test_from_array_emails_null_when_not_provided(): void
    {
        $dto = CustomerData::fromArray(['first_name' => 'Test']);
        $this->assertNull($dto->emails);
    }

    public function test_from_array_handles_phones_array(): void
    {
        $dto = CustomerData::fromArray([
            'first_name' => 'Test',
            'phones' => [
                ['number' => '555-1234', 'type' => 'mobile'],
            ],
        ]);

        $this->assertIsArray($dto->phones);
        $this->assertCount(1, $dto->phones);
        $this->assertSame('555-1234', $dto->phones[0]['number']);
    }

    public function test_from_array_maps_social_profiles(): void
    {
        $dto = CustomerData::fromArray([
            'first_name' => 'Test',
            'social_profiles' => ['twitter' => '@johndoe', 'linkedin' => 'john-doe'],
        ]);

        $this->assertIsArray($dto->socialProfiles);
        $this->assertSame('@johndoe', $dto->socialProfiles['twitter']);
    }

    public function test_from_array_maps_websites(): void
    {
        $dto = CustomerData::fromArray([
            'first_name' => 'Test',
            'websites' => ['https://example.com'],
        ]);

        $this->assertIsArray($dto->websites);
        $this->assertSame('https://example.com', $dto->websites[0]);
    }

    // ─── toArray ──────────────────────────────────────────────────────────────

    public function test_to_array_omits_null_fields(): void
    {
        $dto = new CustomerData(firstName: 'John');
        $arr = $dto->toArray();

        $this->assertArrayHasKey('first_name', $arr);
        $this->assertArrayNotHasKey('last_name', $arr);
        $this->assertArrayNotHasKey('email', $arr);
    }

    public function test_to_array_includes_all_set_fields(): void
    {
        $dto = new CustomerData(
            firstName: 'John',
            lastName: 'Smith',
            email: 'john@example.com',
            company: 'ACME',
            city: 'NYC',
        );

        $arr = $dto->toArray();

        $this->assertArrayHasKey('last_name', $arr);
        $this->assertArrayHasKey('city', $arr);
        $this->assertSame('John', $arr['first_name']);
        $this->assertSame('Smith', $arr['last_name']);
    }

    public function test_to_array_excludes_email_from_output(): void
    {
        // email is a constructor param but NOT in toArray - it's only stored on the customer record separately
        $dto = new CustomerData(firstName: 'John', email: 'john@example.com');
        $arr = $dto->toArray();

        // Note: email is not included in toArray output by design
        $this->assertArrayNotHasKey('email', $arr);
    }

    public function test_validation_customer_data_requires_first_name_as_only_mandatory_field(): void
    {
        // Validation boundary: first_name is the only required field in CustomerData —
        // all other fields are optional (nullable). This enforces minimal data collection
        // while still ensuring the customer has an identity for authorization checks.
        $minimal = new CustomerData(firstName: 'RequiredOnly');

        $this->assertSame(
            'RequiredOnly',
            $minimal->firstName,
            'Validation: firstName must be present and non-null'
        );
        $this->assertNull(
            $minimal->lastName,
            'Validation: lastName is optional and must default to null'
        );
        $this->assertNull(
            $minimal->email,
            'Validation: email is optional and must default to null when not provided'
        );
    }
}
