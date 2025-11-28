<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * Data Transfer Object for mailbox configuration data.
 *
 * Provides type-safe wrapper for mailbox connection settings
 * with immutable readonly properties.
 */
final readonly class MailboxData
{
    /**
     * Create a new MailboxData instance.
     *
     * @param string $name Mailbox name
     * @param string $email Mailbox email address
     * @param string|null $inServer Incoming (IMAP) server
     * @param int|null $inPort Incoming server port
     * @param string|null $inUsername Incoming server username
     * @param string|null $inPassword Incoming server password
     * @param string $inProtocol Incoming protocol (imap/pop3)
     * @param string $inEncryption Incoming encryption (ssl/tls/none)
     * @param string|null $outServer Outgoing (SMTP) server
     * @param int|null $outPort Outgoing server port
     * @param string|null $outUsername Outgoing server username
     * @param string|null $outPassword Outgoing server password
     * @param string $outEncryption Outgoing encryption (ssl/tls/none)
     * @param string $outMethod Outgoing method (smtp/php/sendmail)
     * @param bool $autoReplyEnabled Whether auto-reply is enabled
     * @param string|null $autoReplySubject Auto-reply subject
     * @param string|null $autoReplyMessage Auto-reply message
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $inServer = null,
        public ?int $inPort = null,
        public ?string $inUsername = null,
        public ?string $inPassword = null,
        public string $inProtocol = 'imap',
        public string $inEncryption = 'ssl',
        public ?string $outServer = null,
        public ?int $outPort = null,
        public ?string $outUsername = null,
        public ?string $outPassword = null,
        public string $outEncryption = 'ssl',
        public string $outMethod = 'smtp',
        public bool $autoReplyEnabled = false,
        public ?string $autoReplySubject = null,
        public ?string $autoReplyMessage = null,
    ) {}

    /**
     * Create MailboxData from a request array.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            email: $data['email'] ?? '',
            inServer: $data['in_server'] ?? null,
            inPort: isset($data['in_port']) ? (int) $data['in_port'] : null,
            inUsername: $data['in_username'] ?? null,
            inPassword: $data['in_password'] ?? null,
            inProtocol: $data['in_protocol'] ?? 'imap',
            inEncryption: $data['in_encryption'] ?? 'ssl',
            outServer: $data['out_server'] ?? null,
            outPort: isset($data['out_port']) ? (int) $data['out_port'] : null,
            outUsername: $data['out_username'] ?? null,
            outPassword: $data['out_password'] ?? null,
            outEncryption: $data['out_encryption'] ?? 'ssl',
            outMethod: $data['out_method'] ?? 'smtp',
            autoReplyEnabled: (bool) ($data['auto_reply_enabled'] ?? false),
            autoReplySubject: $data['auto_reply_subject'] ?? null,
            autoReplyMessage: $data['auto_reply_message'] ?? null,
        );
    }

    /**
     * Check if incoming mail is configured.
     */
    public function hasIncomingConfig(): bool
    {
        return $this->inServer !== null && $this->inServer !== '';
    }

    /**
     * Check if outgoing mail is configured.
     */
    public function hasOutgoingConfig(): bool
    {
        return $this->outServer !== null && $this->outServer !== '';
    }

    /**
     * Convert to array for model creation/update.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'in_server' => $this->inServer,
            'in_port' => $this->inPort,
            'in_username' => $this->inUsername,
            'in_password' => $this->inPassword,
            'in_protocol' => $this->inProtocol,
            'in_encryption' => $this->inEncryption,
            'out_server' => $this->outServer,
            'out_port' => $this->outPort,
            'out_username' => $this->outUsername,
            'out_password' => $this->outPassword,
            'out_encryption' => $this->outEncryption,
            'out_method' => $this->outMethod,
            'auto_reply_enabled' => $this->autoReplyEnabled,
            'auto_reply_subject' => $this->autoReplySubject,
            'auto_reply_message' => $this->autoReplyMessage,
        ];
    }
}
