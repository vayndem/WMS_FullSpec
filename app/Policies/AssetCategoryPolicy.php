<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AssetCategory;

class AssetCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }
    public function view(User $user, AssetCategory $category): bool
    {
        return true;
    }
    public function create(User $user): bool
    {
        return (int)$user->type === 33;
    }
    public function update(User $user, AssetCategory $category): bool
    {
        return (int)$user->type === 33;
    }
    public function delete(User $user, AssetCategory $category): bool
    {
        return (int)$user->type === 33 && !$category->assets()->exists();
    }
}
