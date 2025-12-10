<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:install 
                            {--email= : Admin email address}
                            {--password= : Admin password}
                            {--first_name= : Admin first name}
                            {--last_name= : Admin last name}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install FreeScout (Run migrations and create admin user)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Installing FreeScout...');

        // Run migrations
        $this->info('Running migrations...');
        $this->call('migrate', ['--force' => true]);

        // Create Admin User
        $this->info('Creating admin user...');

        $email = $this->option('email') ?? env('ADMIN_EMAIL');
        $password = $this->option('password') ?? env('ADMIN_PASSWORD');
        $firstName = $this->option('first_name') ?? env('ADMIN_FIRST_NAME', 'Admin');
        $lastName = $this->option('last_name') ?? env('ADMIN_LAST_NAME', 'User');

        if (!$email) {
            if ($this->confirm('Do you want to create an admin user?', true)) {
                $email = $this->ask('Admin Email', 'admin@example.com');
                $firstName = $this->ask('First Name', 'Admin');
                $lastName = $this->ask('Last Name', 'User');
                $password = $this->secret('Admin Password');
            } else {
                $this->info('Skipping admin user creation.');
                return;
            }
        }

        if (!$password) {
             $password = User::generateRandomPassword();
             $this->info("Generated password for {$email}: {$password}");
        }

        $validator = Validator::make([
            'email' => $email,
            'password' => $password,
        ], [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            $this->error('Invalid input:');
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $this->warn("User with email {$email} already exists. Updating password, role and verification status.");
            $user->password = Hash::make($password);
            $user->role = User::ROLE_ADMIN;
            $user->status = User::STATUS_ACTIVE;
            $user->email_verified_at = now();
            $user->save();
        } else {
            User::create([
                'email' => $email,
                'password' => Hash::make($password),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]);
            $this->info("Admin user created successfully.");
        }

        $this->info('FreeScout installed successfully!');
    }
}
