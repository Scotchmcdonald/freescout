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
        $firstName = $validated['first_name'] ?? '';
        if (!is_string($firstName) && !is_int($firstName) && !is_float($firstName)) {
            $firstName = '';
        }

        return new self(
            firstName: (string) $firstName,
            lastName: isset($validated['last_name']) && (is_string($validated['last_name']) || is_int($validated['last_name']) || is_float($validated['last_name'])) ? (string) $validated['last_name'] : null,
            email: isset($validated['email']) && (is_string($validated['email']) || is_int($validated['email']) || is_float($validated['email'])) ? (string) $validated['email'] : null,
            company: isset($validated['company']) && (is_string($validated['company']) || is_int($validated['company']) || is_float($validated['company'])) ? (string) $validated['company'] : null,
            jobTitle: isset($validated['job_title']) && (is_string($validated['job_title']) || is_int($validated['job_title']) || is_float($validated['job_title'])) ? (string) $validated['job_title'] : null,
            phone: isset($validated['phone']) && (is_string($validated['phone']) || is_int($validated['phone']) || is_float($validated['phone'])) ? (string) $validated['phone'] : null,
            timezone: isset($validated['timezone']) && (is_string($validated['timezone']) || is_int($validated['timezone']) || is_float($validated['timezone'])) ? (string) $validated['timezone'] : null,
            address: isset($validated['address']) && (is_string($validated['address']) || is_int($validated['address']) || is_float($validated['address'])) ? (string) $validated['address'] : null,
            city: isset($validated['city']) && (is_string($validated['city']) || is_int($validated['city']) || is_float($validated['city'])) ? (string) $validated['city'] : null,
            state: isset($validated['state']) && (is_string($validated['state']) || is_int($validated['state']) || is_float($validated['state'])) ? (string) $validated['state'] : null,
            zip: isset($validated['zip']) && (is_string($validated['zip']) || is_int($validated['zip']) || is_float($validated['zip'])) ? (string) $validated['zip'] : null,
            country: isset($validated['country']) && (is_string($validated['country']) || is_int($validated['country']) || is_float($validated['country'])) ? (string) $validated['country'] : null,
            notes: isset($validated['notes']) && (is_string($validated['notes']) || is_int($validated['notes']) || is_float($validated['notes'])) ? (string) $validated['notes'] : null,
            emails: isset($validated['emails']) && is_array($validated['emails']) ? array_map(fn ($e) => [
                'email' => is_array($e) && isset($e['email']) && (is_string($e['email']) || is_int($e['email']) || is_float($e['email'])) ? (string) $e['email'] : '',
                'type' => is_array($e) && isset($e['type']) && (is_string($e['type']) || is_int($e['type']) || is_float($e['type'])) ? (string) $e['type'] : ''
            ], $validated['emails']) : null,
            phones: isset($validated['phones']) && is_array($validated['phones']) ? array_map(fn ($p) => [
                'number' => is_array($p) && isset($p['number']) && (is_string($p['number']) || is_int($p['number']) || is_float($p['number'])) ? (string) $p['number'] : '',
                'type' => is_array($p) && isset($p['type']) && (is_string($p['type']) || is_int($p['type']) || is_float($p['type'])) ? (string) $p['type'] : ''
            ], $validated['phones']) : null,
            socialProfiles: isset($validated['social_profiles']) && is_array($validated['social_profiles']) ? array_map(fn ($v) => is_string($v) || is_int($v) || is_float($v) ? (string) $v : '', $validated['social_profiles']) : null,
            websites: isset($validated['websites']) && is_array($validated['websites']) ? array_map(fn ($v) => is_string($v) || is_int($v) || is_float($v) ? (string) $v : '', $validated['websites']) : null,
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
