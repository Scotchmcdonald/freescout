<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Http\Requests\ValidateSmtpRequest;

/**
 * Immutable carrier for SMTP configuration data.
 *
 * Scalar-only — no Eloquent models, no HTTP Request objects.
 * Encryption values: 0 = none, 1 = SSL, 2 = TLS (matches legacy integer convention).
 */
readonly class SmtpSettingsData
{
    public function __construct(
        public string $outServer,
        public int $outPort,
        public string $email,
        public int $outEncryption = 0,
        public ?string $outUsername = null,
        public ?string $outPassword = null,
    ) {}

    /**
     * Construct from a legacy raw array.
     * Missing keys become zero-values; non-numeric port becomes 0.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            outServer: is_string($data['out_server'] ?? null) ? $data['out_server'] : '',
            outPort: (static function () use ($data): int {
            $port = $data['out_port'] ?? null;
            if ($port === null || $port === '' || $port === 0) {
                return 0;
            }
            if (is_numeric($port)) {
                return intval($port);
            }
            // Non-numeric, non-empty string — use -1 to signal an invalid value
            // so the service returns the range error rather than "required".
            return -1;
        })(),
            email: is_string($data['email'] ?? null) ? $data['email'] : '',
            outEncryption: is_numeric($data['out_encryption'] ?? null) ? intval($data['out_encryption']) : 0,
            outUsername: isset($data['out_username']) && is_string($data['out_username']) ? $data['out_username'] : null,
            outPassword: isset($data['out_password']) && is_string($data['out_password']) ? $data['out_password'] : null,
        );
    }

    public static function fromRequest(ValidateSmtpRequest $request): self
    {
        /** @var array{out_server: string, out_port: int|string, email: string, out_encryption?: int|string|null, out_username?: string|null, out_password?: string|null} $validated */
        $validated = $request->validated();

        return new self(
            outServer: $validated['out_server'],
            outPort: intval($validated['out_port']),
            email: $validated['email'],
            outEncryption: isset($validated['out_encryption']) ? intval($validated['out_encryption']) : 0,
            outUsername: $validated['out_username'] ?? null,
            outPassword: $validated['out_password'] ?? null,
        );
    }
}
