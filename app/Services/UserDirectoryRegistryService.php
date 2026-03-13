<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\UserProvider;

class UserDirectoryRegistryService
{
    /**
     * @var UserProvider[]
     */
    protected array $providers = [];

    public function register(UserProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAllUsers(): array
    {
        $allUsers = [];

        foreach ($this->providers as $provider) {
            try {
                $users = $provider->getUsers();
                // Add source to each user
                foreach ($users as &$user) {
                    $user['source'] = $provider->getSourceName();
                }
                $allUsers = array_merge($allUsers, $users);
            } catch (\Exception $e) {
                // Log error but continue with other providers
                \Illuminate\Support\Facades\Log::error('Failed to fetch users from '.$provider->getSourceName().': '.$e->getMessage());
            }
        }

        return $allUsers;
    }
}
