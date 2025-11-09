<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Mailbox;
use App\Models\Option;
use App\Services\ImapService;
use App\Services\SmtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display general settings.
     */
    public function index(): View
    {
        $settings = Option::query()->pluck('value', 'name')->toArray();

        /** @var view-string $viewName */
        $viewName = 'settings.index';
        return view($viewName, compact('settings'));
    }

    /**
     * Update general settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'next_ticket' => 'nullable|integer|min:1',
            'user_permissions' => 'nullable|array',
            'email_branding' => 'nullable|boolean',
            'open_tracking' => 'nullable|boolean',
            'enrich_customer_data' => 'nullable|boolean',
        ]);

        foreach ($validated as $name => $value) {
            Option::updateOrCreate(
                ['name' => $name],
                ['value' => is_bool($value) ? (int) $value : $value]
            );
        }

        // Clear cache
        Cache::flush();

        return back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Display email settings.
     */
    public function email(): View
    {
        $settings = Option::whereIn('name', [
            'mail_driver',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
        ])->pluck('value', 'name')->toArray();

        /** @var view-string $viewName */
        $viewName = 'settings.email';
        return view($viewName, compact('settings'));
    }

    /**
     * Update email settings.
     */
    public function updateEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mail_driver' => 'required|string|in:smtp,sendmail,mailgun,ses,postmark',
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|integer',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|string|in:tls,ssl',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ]);

        foreach ($validated as $name => $value) {
            if ($name === 'mail_password' && empty($value)) {
                continue; // Don't update password if empty
            }

            Option::updateOrCreate(
                ['name' => $name],
                ['value' => $value]
            );
        }

        // Update .env file
        $this->updateEnvFile($validated);

        return back()->with('success', 'Email settings updated successfully.');
    }

    /**
     * Display system settings.
     */
    public function system(): View
    {
        $settings = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_connection' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'queue_connection' => config('queue.default'),
            'session_driver' => config('session.driver'),
        ];

        /** @var view-string $viewName */
        $viewName = 'settings.system';
        return view($viewName, compact('settings'));
    }

    /**
     * Clear application cache.
     */
    public function clearCache(): RedirectResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return back()->with('success', 'Cache cleared successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to clear cache: '.$e->getMessage());
        }
    }

    /**
     * Run database migrations.
     */
    public function migrate(): RedirectResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true]);

            return back()->with('success', 'Migrations completed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Migration failed: '.$e->getMessage());
        }
    }

    /**
     * Test SMTP connection for a mailbox.
     */
    public function testSmtp(Request $request, SmtpService $smtpService): JsonResponse
    {
        $validated = $request->validate([
            'mailbox_id' => 'required|exists:mailboxes,id',
            'test_email' => 'required|email',
        ]);

        try {
            /** @var \App\Models\Mailbox $mailbox */
            $mailbox = Mailbox::findOrFail($validated['mailbox_id']);

            if (empty($mailbox->out_server)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No SMTP server configured for this mailbox.',
                ], 400);
            }

            $result = $smtpService->testConnection($mailbox, $validated['test_email']);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test IMAP connection for a mailbox.
     */
    public function testImap(Request $request, ImapService $imapService): JsonResponse
    {
        $validated = $request->validate([
            'mailbox_id' => 'required|exists:mailboxes,id',
        ]);

        try {
            /** @var \App\Models\Mailbox $mailbox */
            $mailbox = Mailbox::findOrFail($validated['mailbox_id']);

            if (empty($mailbox->in_server)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No IMAP server configured for this mailbox.',
                ], 400);
            }

            $result = $imapService->testConnection($mailbox);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate SMTP settings.
     */
    public function validateSmtp(Request $request, SmtpService $smtpService): JsonResponse
    {
        $errors = $smtpService->validateSettings($request->all());

        if (! empty($errors)) {
            return response()->json([
                'success' => false,
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'SMTP settings are valid.',
        ]);
    }

    /**
     * Update .env file with new values.
     * 
     * @param array<string, mixed> $data
     */
    protected function updateEnvFile(array $data): void
    {
        $envFile = base_path('.env');

        if (! file_exists($envFile)) {
            return;
        }

        $content = file_get_contents($envFile);

        $mapping = [
            'mail_driver' => 'MAIL_MAILER',
            'mail_host' => 'MAIL_HOST',
            'mail_port' => 'MAIL_PORT',
            'mail_username' => 'MAIL_USERNAME',
            'mail_password' => 'MAIL_PASSWORD',
            'mail_encryption' => 'MAIL_ENCRYPTION',
            'mail_from_address' => 'MAIL_FROM_ADDRESS',
            'mail_from_name' => 'MAIL_FROM_NAME',
        ];

        foreach ($data as $key => $value) {
            if (isset($mapping[$key]) && ! empty($value)) {
                $envKey = $mapping[$key];
                $pattern = "/^{$envKey}=.*/m";
                $content = $content ?: ''; // Ensure content is string

                if (preg_match($pattern, $content)) {
                    $content = preg_replace($pattern, "{$envKey}={$value}", $content);
                } else {
                    $content .= "\n{$envKey}={$value}";
                }
            }
        }

        file_put_contents($envFile, $content);
    }
}
