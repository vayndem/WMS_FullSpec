<?php

namespace App\Policies;

use App\Models\ApiUser;
use App\Models\ServiceCategory;

class ServiceCategoryPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return in_array((int)$user->type, [5, 33], true);
    }
    public function update(ApiUser $user, ServiceCategory $category): bool
    {
        return (int)$user->type === 33;
    }
}
