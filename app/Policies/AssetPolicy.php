<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Asset;

class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }
    public function view(User $user, Asset $asset): bool
    {
        return true;
    }
    public function viewFinancials(User $user): bool
    {
        return in_array((int)$user->type, [5, 33], true);
    }
    public function create(User $user): bool
    {
        return (int)$user->type === 33;
    }
    public function update(User $user, Asset $asset): bool
    {
        return (int)$user->type === 33 && $asset->status === 'ACTIVE';
    }
    public function depreciate(User $user, Asset $asset): bool
    {
        return $this->update($user, $asset);
    }
    public function dispose(User $user, Asset $asset): bool
    {
        return $this->update($user, $asset);
    }
}
