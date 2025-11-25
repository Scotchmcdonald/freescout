<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\Alert;
use App\Models\Mailbox;
use App\Models\Option;
use App\Services\ImapService;
use App\Services\SmtpService;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    /**
     * Get all settings sections.
     */
    protected function getSections(): array
    {
        $sections = [
            'general' => [
                'title' => __('General'),
                'route' => 'settings',
                'icon' => 'cog',
                'order' => 100
            ],
            'email' => [
                'title' => __('Email Settings'),
                'route' => 'settings.email',
                'icon' => 'mail',
                'order' => 200
            ],
            'alerts' => [
                'title' => __('Alerts'),
                'route' => 'settings.alerts',
                'icon' => 'bell',
                'order' => 300
            ],
            'system' => [
                'title' => __('System'),
                'route' => 'settings.system',
                'icon' => 'server',
                'order' => 400
            ],
        ];

        // Allow modules to add/remove sections
        return \Eventy::filter('settings.sections', $sections);
    }

    /**
     * Display general settings.
     */
    public function index(): View|ViewFactory
    {
        $settings = Option::query()->pluck('value', 'name')->toArray();
        $sections = $this->getSections();
        $currentSection = 'general';

        // Parse user_permissions if stored as JSON
        if (isset($settings['user_permissions']) && is_string($settings['user_permissions'])) {
            $settings['user_permissions'] = json_decode($settings['user_permissions'], true) ?: [];
        }

        // Get available user permissions for display
        $userPermissions = [];
        if (class_exists('\App\Models\User') && method_exists('\App\Models\User', 'getUserPermissionsList')) {
            $permissionIds = \App\Models\User::getUserPermissionsList();
            foreach ($permissionIds as $permissionId) {
                $userPermissions[$permissionId] = \App\Models\User::getUserPermissionName($permissionId);
            }
        }

        // Allow modules to modify settings
        $settings = \Eventy::filter('settings.section_settings', $settings, $currentSection);
        $settings = \Eventy::filter('settings.alter_section_settings', $settings, $currentSection);

        return view('settings.index', compact('settings', 'sections', 'currentSection', 'userPermissions'));
    }

    /**
     * Update general settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $currentSection = 'general';
        
        // Allow modules to modify request before saving
        $request = \Eventy::filter('settings.before_save', $request, $currentSection, []);

        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'custom_number' => 'nullable|boolean',
            'next_ticket' => 'nullable|integer|min:1',
            'locale' => 'nullable|string|max:10',
            'timezone' => 'nullable|timezone',
            'time_format' => 'nullable|in:12,24',
            'email_conv_history' => 'nullable|in:none,last,full',
            'max_message_size' => 'nullable|integer|min:0|max:102400',
            'email_branding' => 'nullable|boolean',
            'open_tracking' => 'nullable|boolean',
            'enrich_customer_data' => 'nullable|boolean',
            'user_permissions' => 'nullable|array',
            'user_permissions.*' => 'nullable|integer',
        ]);

        foreach ($validated as $name => $value) {
            if ($value === null) {
                continue;
            }
            
            // Handle arrays (like user_permissions)
            if (is_array($value)) {
                $value = json_encode($value);
            } elseif (is_bool($value)) {
                $value = (int) $value;
            }
            
            Option::updateOrCreate(
                ['name' => $name],
                ['value' => $value]
            );
        }

        // Clear cache
        Cache::flush();

        // Allow modules to perform actions after save
        $response = back()->with('success', 'Settings updated successfully.');
        $response = \Eventy::filter('settings.after_save', $response, $request, $currentSection, $validated);

        return $response;
    }

    /**
     * Display email settings.
     */
    public function email(): View|ViewFactory
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

        $sections = $this->getSections();
        $currentSection = 'email';

        return view('settings.email', compact('settings', 'sections', 'currentSection'));
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
    public function system(): View|ViewFactory
    {
        $settings = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_connection' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'queue_connection' => config('queue.default'),
            'session_driver' => config('session.driver'),
        ];

        $sections = $this->getSections();
        $currentSection = 'system';

        return view('settings.system', compact('settings', 'sections', 'currentSection'));
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
     * Display alert settings.
     */
    public function alerts(): View|ViewFactory
    {
        $settings = Option::whereIn('name', [
            'alert_system_errors',
            'alert_high_queue',
            'alert_failed_jobs',
            'alert_disk_space',
            'alert_db_connection',
            'queue_threshold',
            'alert_recipients',
        ])->pluck('value', 'name')->toArray();

        $sections = $this->getSections();
        $currentSection = 'alerts';

        return view('settings.alerts', compact('settings', 'sections', 'currentSection'));
    }

    /**
     * Update alert settings.
     */
    public function updateAlerts(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'alerts' => 'nullable|array',
            'alerts.system_errors' => 'nullable|boolean',
            'alerts.high_queue' => 'nullable|boolean',
            'alerts.failed_jobs' => 'nullable|boolean',
            'alerts.disk_space' => 'nullable|boolean',
            'alerts.db_connection' => 'nullable|boolean',
            'queue_threshold' => 'nullable|integer|min:10|max:10000',
            'alert_recipients' => 'nullable|string',
        ]);

        // Handle test alert action
        if ($request->input('action') === 'test') {
            return $this->sendTestAlert($request);
        }

        // Update alert settings
        $alerts = $validated['alerts'] ?? [];
        
        Option::updateOrCreate(
            ['name' => 'alert_system_errors'],
            ['value' => (int) ($alerts['system_errors'] ?? false)]
        );
        
        Option::updateOrCreate(
            ['name' => 'alert_high_queue'],
            ['value' => (int) ($alerts['high_queue'] ?? false)]
        );
        
        Option::updateOrCreate(
            ['name' => 'alert_failed_jobs'],
            ['value' => (int) ($alerts['failed_jobs'] ?? false)]
        );
        
        Option::updateOrCreate(
            ['name' => 'alert_disk_space'],
            ['value' => (int) ($alerts['disk_space'] ?? false)]
        );
        
        Option::updateOrCreate(
            ['name' => 'alert_db_connection'],
            ['value' => (int) ($alerts['db_connection'] ?? false)]
        );

        if (isset($validated['queue_threshold'])) {
            Option::updateOrCreate(
                ['name' => 'queue_threshold'],
                ['value' => $validated['queue_threshold']]
            );
        }

        if (isset($validated['alert_recipients'])) {
            Option::updateOrCreate(
                ['name' => 'alert_recipients'],
                ['value' => $validated['alert_recipients']]
            );
        }

        return back()->with('success', 'Alert settings updated successfully.');
    }

    /**
     * Send test alert email.
     */
    protected function sendTestAlert(Request $request): RedirectResponse
    {
        $recipients = $request->input('alert_recipients', '');
        $recipients = is_string($recipients) ? $recipients : '';
        $emails = array_filter(array_map('trim', explode("\n", $recipients)));

        if (empty($emails)) {
            return back()->with('error', 'No recipients configured for alerts.');
        }

        try {
            $sentCount = 0;
            foreach ($emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    Mail::to($email)->send(new Alert(
                        'Test Alert',
                        'This is a test alert from FreeScout. Your alert configuration is working correctly.'
                    ));
                    $sentCount++;
                }
            }

            if ($sentCount === 0) {
                return back()->with('error', 'No valid email addresses found in recipients.');
            }

            return back()->with('success', 'Test alert sent successfully to ' . $sentCount . ' recipient(s).');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send test alert: ' . $e->getMessage());
        }
    }

    /**
     * Update .env file with new values.
     *
     * @param  array<string, mixed>  $data
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
                $value = is_string($value) ? $value : (is_scalar($value) ? (string)$value : '');
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

    /**
     * Display general settings (alias for index).
     */
    public function general(): View|ViewFactory
    {
        return $this->index();
    }

    /**
     * Display security settings.
     */
    public function security(Request $request): View|ViewFactory
    {
        $settings = Option::query()->pluck('value', 'name')->toArray();
        $sections = $this->getSections();
        $currentSection = 'security';

        if (view()->exists('settings.security')) {
            return view('settings.security', compact('settings', 'sections', 'currentSection'));
        }

        return view('settings.index', compact('settings', 'sections', 'currentSection'));
    }
}
