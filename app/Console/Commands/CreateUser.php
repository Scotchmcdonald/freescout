<?php

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
    protected $signature = 'freescout:create-user {--role=} {--firstName=} {--lastName=} {--email=} {--password=} {--no-verification}';

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
        $lastName = $this->option('lastName') ?: $this->ask('User last name');
        $email = $this->option('email') ?: $this->ask('User email address');
        $password = $this->option('password') ?: $this->secret('User password');

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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        $user = new User();
        $user->role = $role === 'admin' ? User::ROLE_ADMIN : User::ROLE_USER;
        $user->first_name = $firstName;
        $user->last_name = $lastName;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->status = User::STATUS_ACTIVE;

        if (!$this->option('no-verification')) {
            $user->email_verified_at = now();
        }

        if ($this->confirm('Do you want to create the user?', true)) {
            try {
                $user->save();
                $this->info('User created with id: ' . $user->id);
            } catch (\Exception $e) {
                $this->error('Error creating user: ' . $e->getMessage());
                return 1;
            }
        }

        return 0;
    }
}
