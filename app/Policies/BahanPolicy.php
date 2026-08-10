<?php

namespace App\Policies;

use App\Models\Bahan;
use App\Models\User;

class BahanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Bahan $bahan): bool
    {
        return true;
    }

    public function viewFinancials(User $user): bool
    {
        return $user->isAccounting();
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_WAREHOUSE, User::ROLE_ACCOUNTING]);
    }

    public function update(User $user, Bahan $bahan): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_WAREHOUSE, User::ROLE_ACCOUNTING]);
    }

    public function delete(User $user, Bahan $bahan): bool
    {
        return false;
    }
}
