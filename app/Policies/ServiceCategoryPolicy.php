<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ServiceCategory;

class ServiceCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }
    public function update(User $user, ServiceCategory $category): bool
    {
        return $user->isAccounting();
    }
}
