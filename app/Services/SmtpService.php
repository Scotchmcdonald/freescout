<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Mailbox;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SmtpService
{
    /**
     * Test SMTP connection by sending a test email.
     */
    public function testConnection(Mailbox $mailbox, string $testEmailAddress): array
    {
        $result = [
            'success' => false,
            'message' => '',
        ];

        // Validate settings before attempting to send
        $validationErrors = $this->validateMailboxSettings($mailbox);
        if (! empty($validationErrors)) {
            $result['message'] = 'Configuration errors: '.implode(', ', $validationErrors);
            Log::warning('SMTP test skipped due to invalid configuration', [
                'mailbox_id' => $mailbox->id,
                'errors' => $validationErrors,
            ]);

            return $result;
        }

        Log::info('Starting SMTP test', [
            'mailbox_id' => $mailbox->id,
            'mailbox_name' => $mailbox->name,
            'to_email' => $testEmailAddress,
            'smtp_server' => $mailbox->out_server,
            'smtp_port' => $mailbox->out_port,
            'encryption' => $this->getEncryption($mailbox->out_encryption),
        ]);

        try {
            // Configure SMTP settings dynamically
            $this->configureSmtp($mailbox);

            Log::debug('SMTP configuration applied', [
                'mailbox_id' => $mailbox->id,
            ]);

            // Send test email
            Mail::raw('This is a test email from FreeScout to verify SMTP configuration.', function ($message) use ($mailbox, $testEmailAddress) {
                $message->to($testEmailAddress)
                    ->from($mailbox->email, $mailbox->name)
                    ->subject('FreeScout SMTP Test - '.date('Y-m-d H:i:s'));
            });

            $result['success'] = true;
            $result['message'] = "Test email sent successfully to {$testEmailAddress}. Please check your inbox (and spam folder).";

            Log::info('SMTP test successful', [
                'mailbox_id' => $mailbox->id,
                'mailbox_name' => $mailbox->name,
                'to_email' => $testEmailAddress,
                'from_email' => $mailbox->email,
            ]);
        } catch (\Swift_TransportException $e) {
            $result['message'] = 'SMTP connection error: '.$e->getMessage();
            Log::error('SMTP transport error', [
                'mailbox_id' => $mailbox->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } catch (\Exception $e) {
            $result['message'] = 'SMTP test failed: '.$e->getMessage();
            Log::error('SMTP test failed', [
                'mailbox_id' => $mailbox->id,
                'mailbox_name' => $mailbox->name,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $result;
    }

    /**
     * Validate mailbox SMTP settings.
     */
    protected function validateMailboxSettings(Mailbox $mailbox): array
    {
        $errors = [];

        if (empty($mailbox->out_server)) {
            $errors[] = 'SMTP server not configured';
        }

        if (empty($mailbox->out_port)) {
            $errors[] = 'SMTP port not configured';
        }

        if (empty($mailbox->email)) {
            $errors[] = 'From email address not configured';
        } elseif (! filter_var($mailbox->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid from email address';
        }

        return $errors;
    }

    /**
     * Configure SMTP settings dynamically for a mailbox.
     */
    public function configureSmtp(Mailbox $mailbox): void
    {
        $encryption = $this->getEncryption($mailbox->out_encryption);

        Log::debug('Configuring SMTP', [
            'mailbox_id' => $mailbox->id,
            'host' => $mailbox->out_server,
            'port' => $mailbox->out_port,
            'encryption' => $encryption,
            'username' => $mailbox->out_username,
            'from_email' => $mailbox->email,
        ]);

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp', [
            'transport' => 'smtp',
            'host' => $mailbox->out_server,
            'port' => $mailbox->out_port,
            'encryption' => $encryption,
            'username' => $mailbox->out_username,
            'password' => $mailbox->out_password,
            'timeout' => null,
        ]);
        Config::set('mail.from', [
            'address' => $mailbox->email,
            'name' => $mailbox->name,
        ]);
    }

    /**
     * Get encryption protocol.
     */
    protected function getEncryption(?int $encryption): ?string
    {
        return match ($encryption) {
            1 => 'ssl',
            2 => 'tls',
            default => null,
        };
    }

    /**
     * Validate SMTP settings without sending email.
     */
    public function validateSettings(array $settings): array
    {
        $errors = [];

        if (empty($settings['out_server'])) {
            $errors['out_server'] = 'SMTP server is required';
        }

        if (empty($settings['out_port'])) {
            $errors['out_port'] = 'SMTP port is required';
        } elseif (! is_numeric($settings['out_port']) || $settings['out_port'] < 1 || $settings['out_port'] > 65535) {
            $errors['out_port'] = 'SMTP port must be between 1 and 65535';
        }

        if (empty($settings['email'])) {
            $errors['email'] = 'Email address is required';
        } elseif (! filter_var($settings['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address';
        }

        // Check common SMTP port/encryption combinations
        if (! empty($settings['out_port']) && is_numeric($settings['out_port'])) {
            $port = (int) $settings['out_port'];
            $encryption = $settings['out_encryption'] ?? 0;

            if ($port === 465 && $encryption !== 1) {
                $errors['out_encryption'] = 'Port 465 typically requires SSL encryption';
            } elseif ($port === 587 && $encryption !== 2) {
                $errors['out_encryption'] = 'Port 587 typically requires TLS encryption';
            }
        }

        Log::debug('SMTP settings validation', [
            'errors' => $errors,
            'settings' => array_merge($settings, ['out_password' => '***REDACTED***']),
        ]);

        return $errors;
    }
}
