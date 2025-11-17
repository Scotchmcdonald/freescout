<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

/**
 * Mock class for IMAP address objects used in testing.
 * Simulates the behavior of Webklex\PHPIMAP address objects.
 */
class MockImapAddress
{
    public ?string $mail = null;
    public ?string $email = null;
    public ?string $personal = null;
    public ?string $name = null;
    public ?string $host = null;
    public ?string $mailbox = null;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function __toString(): string
    {
        $email = $this->mail ?? $this->email ?? '';
        $name = $this->personal ?? $this->name ?? '';

        if ($name && $email) {
            return "$name <$email>";
        }

        return $email;
    }

    public static function create(string $email, ?string $name = null): self
    {
        return new self([
            'mail' => $email,
            'email' => $email,
            'personal' => $name,
            'name' => $name,
        ]);
    }
}
