<?php

namespace App\Policies;

use App\Models\User;
use Modules\Crm\Models\ClientUser;
use Modules\AssetManagement\Entities\Asset;
use Illuminate\Auth\Access\HandlesAuthorization;

class AssetPolicy
{
    use HandlesAuthorization;

    public function viewAny(User|ClientUser $user): bool
    {
        if ($user instanceof User) {
            return true;
        }
        return false;
    }

    public function view(User|ClientUser $user, Asset $asset): bool
    {
        if ($user instanceof User) {
            if ($user->isAdmin()) {
                return true;
            }
            if (!$asset->client || !$asset->client->company_id) {
                return false;
            }
            return $user->hasCompanyAccess($asset->client->company_id);
        }
        return false;
    }

    public function create(User|ClientUser $user): bool
    {
        return $user instanceof User && $user->isAdmin();
    }

    public function update(User|ClientUser $user, Asset $asset): bool
    {
        return $user instanceof User && $user->isAdmin();
    }

    public function delete(User|ClientUser $user, Asset $asset): bool
    {
        return $user instanceof User && $user->isAdmin();
    }
}
