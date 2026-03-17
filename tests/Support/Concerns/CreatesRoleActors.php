<?php

declare(strict_types=1);

namespace Tests\Support\Concerns;

use App\Models\User;
use Modules\Crm\Models\Company;

trait CreatesRoleActors
{
    protected function makeAdminActor(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'type' => User::TYPE_INTERNAL,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    protected function makeUserActorForCompany(Company $company): User
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
            'type' => User::TYPE_INTERNAL,
            'status' => User::STATUS_ACTIVE,
        ]);

        $user->companies()->attach($company->id, [
            'status' => 'approved',
            'is_primary' => true,
        ]);

        return $user;
    }
}
