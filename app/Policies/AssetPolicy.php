<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Asset;

class AssetPolicy
{
    private function canViewAssets(User $user): bool
    {
        return true;
    }

    private function canViewAssetFinancials(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }

    private function canManageAssets(User $user): bool
    {
        return $user->isAccounting();
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewAssets($user);
    }

    public function view(User $user, Asset $asset): bool
    {
        return $this->canViewAssets($user);
    }

    public function viewFinancials(User $user): bool
    {
        return $this->canViewAssetFinancials($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageAssets($user);
    }

    public function update(User $user, Asset $asset): bool
    {
        return $this->canManageAssets($user) && $asset->status === 'ACTIVE';
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
