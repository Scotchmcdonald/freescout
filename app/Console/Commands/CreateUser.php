<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:create-user {--role=} {--firstName=} {--lastName=} {--email=} {--password=} {--verified} {--overwrite}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $role = $this->option('role');
        if (!$role) {
            $role = $this->choice('User role', ['admin', 'user'], 'admin');
        }

        $firstName = $this->option('firstName') ?: $this->ask('User first name');
        $firstName = is_string($firstName) ? $firstName : (is_scalar($firstName) ? (string) $firstName : '');

        $lastName = $this->option('lastName') ?: $this->ask('User last name');
        $lastName = is_string($lastName) ? $lastName : (is_scalar($lastName) ? (string) $lastName : '');

        $email = $this->option('email') ?: $this->ask('User email address');
        $email = is_string($email) ? $email : (is_scalar($email) ? (string) $email : '');

        $password = $this->option('password') ?: $this->secret('User password');
        $password = is_string($password) ? $password : (is_scalar($password) ? (string) $password : '');

        $validator = Validator::make([
            'role' => $role,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password,
        ], [
            'role' => ['required', 'in:admin,user'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        $user = User::where('email', $email)->first();
        if ($user && !$this->option('overwrite')) {
            if (!$this->confirm("User with email '$email' already exists. Do you want to overwrite it?", false)) {
                $this->error('User already exists.');
                return 1;
            }
        }
        
        if (!$user) {
            $user = new User();
            $user->email = $email;
        }

        $user->role = $role === 'admin' ? User::ROLE_ADMIN : User::ROLE_USER;
        $user->first_name = $firstName;
        $user->last_name = $lastName;
        $user->password = Hash::make($password);
        $user->status = User::STATUS_ACTIVE;

        $verify = true;
        
        // If --verified is present, we force verify. 
        // If running interactively and not specified, we ask.
        // If not interactive and not specified, we assume verified (default true).
        
        if ($this->option('verified')) {
            $verify = true;
        } elseif ($this->option('no-interaction')) {
            $verify = true; 
        } else {
            $verify = $this->confirm('Mark email as verified?', true);
        }

        if ($verify) {
            $user->email_verified_at = now();
        } else {
            $user->email_verified_at = null;
        }

        if ($this->confirm('Do you want to create/update the user?', true)) {
            try {
                $user->save();
                $this->info('User saved with id: ' . $user->id);
            } catch (\Exception $e) {
                $this->error('Error creating user: ' . $e->getMessage());
                return 1;
            }
        }

        return 0;
    }
}
