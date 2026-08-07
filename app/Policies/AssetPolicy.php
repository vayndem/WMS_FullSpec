<?php

namespace App\Policies;

use App\Models\ApiUser;
use App\Models\Asset;

class AssetPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return true;
    }
    public function view(ApiUser $user, Asset $asset): bool
    {
        return true;
    }
    public function viewFinancials(ApiUser $user): bool
    {
        return in_array((int)$user->type, [5, 33], true);
    }
    public function create(ApiUser $user): bool
    {
        return (int)$user->type === 33;
    }
    public function update(ApiUser $user, Asset $asset): bool
    {
        return (int)$user->type === 33 && $asset->status === 'ACTIVE';
    }
    public function depreciate(ApiUser $user, Asset $asset): bool
    {
        return $this->update($user, $asset);
    }
    public function dispose(ApiUser $user, Asset $asset): bool
    {
        return $this->update($user, $asset);
    }
}
