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
        public string $email,
        public ?string $lastName = null,
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
        $firstName = $validated['first_name'] ?? '';
        $email = $validated['email'] ?? '';
        
        return new self(
            firstName: is_string($firstName) || is_int($firstName) || is_float($firstName) ? (string) $firstName : '',
            email: is_string($email) || is_int($email) || is_float($email) ? (string) $email : '',
            lastName: isset($validated['last_name']) && (is_string($validated['last_name']) || is_int($validated['last_name']) || is_float($validated['last_name'])) ? (string) $validated['last_name'] : null,
            password: isset($validated['password']) && (is_string($validated['password']) || is_int($validated['password']) || is_float($validated['password'])) ? (string) $validated['password'] : null,
            role: UserRole::tryFrom(is_numeric($validated['role'] ?? null) ? intval($validated['role']) : UserRole::User->value) ?? UserRole::User,
            status: UserStatus::tryFrom(is_numeric($validated['status'] ?? null) ? intval($validated['status']) : UserStatus::Active->value) ?? UserStatus::Active,
            jobTitle: isset($validated['job_title']) && (is_string($validated['job_title']) || is_int($validated['job_title']) || is_float($validated['job_title'])) ? (string) $validated['job_title'] : null,
            phone: isset($validated['phone']) && (is_string($validated['phone']) || is_int($validated['phone']) || is_float($validated['phone'])) ? (string) $validated['phone'] : null,
            timezone: isset($validated['timezone']) && (is_string($validated['timezone']) || is_int($validated['timezone']) || is_float($validated['timezone'])) ? (string) $validated['timezone'] : null,
            locale: isset($validated['locale']) && (is_string($validated['locale']) || is_int($validated['locale']) || is_float($validated['locale'])) ? (string) $validated['locale'] : null,
            mailboxes: isset($validated['mailboxes']) && is_array($validated['mailboxes']) ? array_map(fn ($id) => is_numeric($id) ? intval($id) : 0, $validated['mailboxes']) : null,
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
