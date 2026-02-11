<?php

declare(strict_types=1);

namespace App\Contracts;

interface UserProvider
{
    /**
     * Get list of users from the provider.
     * 
     * @return array Array of user arrays ['name', 'email', 'status', ...]
     */
    public function getUsers(): array;
    
    /**
     * Get the name of the provider source (e.g. "Google Workspace")
     */
    public function getSourceName(): string;
}
