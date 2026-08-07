<?php

namespace App\Policies;

use App\Models\ApiUser;
use App\Models\AssetCategory;

class AssetCategoryPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return true;
    }
    public function view(ApiUser $user, AssetCategory $category): bool
    {
        return true;
    }
    public function create(ApiUser $user): bool
    {
        return (int)$user->type === 33;
    }
    public function update(ApiUser $user, AssetCategory $category): bool
    {
        return (int)$user->type === 33;
    }
    public function delete(ApiUser $user, AssetCategory $category): bool
    {
        return (int)$user->type === 33 && !$category->assets()->exists();
    }
}
