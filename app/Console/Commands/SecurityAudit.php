<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;

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
    protected $description = 'Run composer audit and email the results if vulnerabilities are found';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running composer audit...');

        $result = Process::run('composer audit --locked --format=plain');

        if ($result->successful()) {
            $this->info('No security vulnerabilities found.');
            return Command::SUCCESS;
        }

        $output = $result->output();
        $this->error('Security vulnerabilities found!');
        $this->line($output);

        $email = $this->option('email') ?? config('app.admin_email') ?? config('mail.from.address', '');
        $recipient = is_string($email) ? $email : '';

        if ($recipient) {
            $this->info("Sending alert to {$recipient}...");
            
            Mail::raw($output, function ($message) use ($recipient) {
                $message->to($recipient)
                    ->subject('⚠️ Security Alert: Composer Audit Failed');
            });
            
            $this->info('Alert sent.');
        } else {
            $this->warn('No recipient email configured. Use --email or set ADMIN_EMAIL in .env');
        }

        return Command::FAILURE;
    }
}
