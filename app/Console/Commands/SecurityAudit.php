<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Throwable;

class SecurityAudit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:audit {--email= : Email address to send the report to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run composer and npm audits and email the results if vulnerabilities are found';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running security audits...');

        // Run Composer Audit
        $this->info('Running composer audit...');
        $composerResult = Process::run('composer audit --locked --format=plain');

        // Run NPM Audit
        $this->info('Running npm audit...');
        $npmResult = Process::run('npm audit');

        if ($composerResult->successful() && $npmResult->successful()) {
            $this->info('No security vulnerabilities found.');

            return Command::SUCCESS;
        }

        $output = '';

        if ($composerResult->failed()) {
            $output .= "=== COMPOSER AUDIT FAILURES ===\n\n";
            $output .= $this->combineProcessOutput($composerResult->output(), $composerResult->errorOutput())."\n\n";
        }

        if ($npmResult->failed()) {
            $output .= "=== NPM AUDIT FAILURES ===\n\n";
            $output .= $this->combineProcessOutput($npmResult->output(), $npmResult->errorOutput())."\n\n";
        }

        $this->error('Security vulnerabilities found!');
        $this->line($output);

        $email = $this->option('email') ?? config('app.admin_email') ?? config('mail.from.address', '');
        $recipient = is_string($email) ? $email : '';

        if ($recipient) {
            $this->attemptAlertEmail($recipient, $output);
        } else {
            $this->warn('No recipient email configured. Use --email or set ADMIN_EMAIL in .env');
        }

        return Command::FAILURE;
    }

    private function combineProcessOutput(string $stdout, string $stderr): string
    {
        $sections = [];

        if (trim($stdout) !== '') {
            $sections[] = trim($stdout);
        }

        if (trim($stderr) !== '') {
            $sections[] = trim($stderr);
        }

        return $sections === [] ? 'No output returned by audit command.' : implode("\n\n", $sections);
    }

    private function attemptAlertEmail(string $recipient, string $output): void
    {
        if (! $this->isMailTransportConfigured()) {
            $this->warn('Mail transport is not configured. Skipping alert email.');

            return;
        }

        $this->info("Sending alert to {$recipient}...");

        try {
            Mail::raw($output, function ($message) use ($recipient) {
                $message->to($recipient)
                    ->subject('Security Alert: Audit Failed');
            });

            $this->info('Alert sent.');
        } catch (Throwable $exception) {
            $this->warn('Unable to send alert email: '.$exception->getMessage());
        }
    }

    private function isMailTransportConfigured(): bool
    {
        $defaultMailer = (string) config('mail.default', '');

        if ($defaultMailer === '') {
            return false;
        }

        $mailerConfig = config("mail.mailers.{$defaultMailer}");
        $transport = Arr::get($mailerConfig, 'transport');

        return is_string($transport) && trim($transport) !== '';
    }
}
