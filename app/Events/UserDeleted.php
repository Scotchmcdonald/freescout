<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserDeleted
{
    use Dispatchable;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public User $deleted_user,
        public User $by_user
    ) {}
}
