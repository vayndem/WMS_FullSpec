<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ServiceCategory;

class ServiceCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array((int)$user->type, [5, 33], true);
    }
    public function update(User $user, ServiceCategory $category): bool
    {
        return (int)$user->type === 33;
    }
}
