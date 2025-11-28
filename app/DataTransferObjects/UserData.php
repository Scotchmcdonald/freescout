<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\UserRole;
use App\Enums\UserStatus;

/**
 * Data Transfer Object for creating/updating a user.
 *
 * Provides a type-safe wrapper for user data.
 */
readonly class UserData
{
    public function __construct(
        public string $firstName,
        public ?string $lastName = null,
        public string $email,
        public ?string $password = null,
        public UserRole $role = UserRole::User,
        public UserStatus $status = UserStatus::Active,
        public ?string $jobTitle = null,
        public ?string $phone = null,
        public ?string $timezone = null,
        public ?string $locale = null,
        /** @var array<int>|null */
        public ?array $mailboxes = null,
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
            email: $validated['email'],
            password: $validated['password'] ?? null,
            role: UserRole::tryFrom((int) ($validated['role'] ?? UserRole::User->value)) ?? UserRole::User,
            status: UserStatus::tryFrom((int) ($validated['status'] ?? UserStatus::Active->value)) ?? UserStatus::Active,
            jobTitle: $validated['job_title'] ?? null,
            phone: $validated['phone'] ?? null,
            timezone: $validated['timezone'] ?? null,
            locale: $validated['locale'] ?? null,
            mailboxes: $validated['mailboxes'] ?? null,
        );
    }

    /**
     * Convert the DTO to an array suitable for model creation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'job_title' => $this->jobTitle,
            'phone' => $this->phone,
        ];

        if ($this->password !== null) {
            $data['password'] = $this->password;
        }

        if ($this->timezone !== null) {
            $data['timezone'] = $this->timezone;
        }

        if ($this->locale !== null) {
            $data['locale'] = $this->locale;
        }

        return $data;
    }

    /**
     * Get the full name of the user.
     */
    public function getFullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }
}
