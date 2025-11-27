<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * Data Transfer Object for creating/updating a customer.
 *
 * Provides a type-safe wrapper for customer data.
 */
readonly class CustomerData
{
    public function __construct(
        public string $firstName,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $company = null,
        public ?string $jobTitle = null,
        public ?string $phone = null,
        public ?string $timezone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $zip = null,
        public ?string $country = null,
        public ?string $notes = null,
        /** @var array<array{email: string, type: string}>|null */
        public ?array $emails = null,
        /** @var array<array{number: string, type: string}>|null */
        public ?array $phones = null,
        /** @var array<string, string>|null */
        public ?array $socialProfiles = null,
        /** @var array<string>|null */
        public ?array $websites = null,
    ) {}

    /**
     * Create a DTO from a validated request array.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromArray(array $validated): self
    {
        return new self(
            firstName: $validated['first_name'],
            lastName: $validated['last_name'] ?? null,
            email: $validated['email'] ?? null,
            company: $validated['company'] ?? null,
            jobTitle: $validated['job_title'] ?? null,
            phone: $validated['phone'] ?? null,
            timezone: $validated['timezone'] ?? null,
            address: $validated['address'] ?? null,
            city: $validated['city'] ?? null,
            state: $validated['state'] ?? null,
            zip: $validated['zip'] ?? null,
            country: $validated['country'] ?? null,
            notes: $validated['notes'] ?? null,
            emails: $validated['emails'] ?? null,
            phones: $validated['phones'] ?? null,
            socialProfiles: $validated['social_profiles'] ?? null,
            websites: $validated['websites'] ?? null,
        );
    }

    /**
     * Convert the DTO to an array suitable for model creation/update.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'company' => $this->company,
            'job_title' => $this->jobTitle,
            'phone' => $this->phone,
            'timezone' => $this->timezone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'country' => $this->country,
            'notes' => $this->notes,
            'emails' => $this->emails,
            'phones' => $this->phones,
            'social_profiles' => $this->socialProfiles,
            'websites' => $this->websites,
        ], fn ($value) => $value !== null);
    }

    /**
     * Get the full name of the customer.
     */
    public function getFullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }
}
