<?php

namespace App\Policies;

use App\Models\Bahan;
use App\Models\ApiUser;

class BahanPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return true;
    }

    public function view(ApiUser $user, Bahan $bahan): bool
    {
        return true;
    }

    public function viewFinancials(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function create(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function update(ApiUser $user, Bahan $bahan): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function delete(ApiUser $user, Bahan $bahan): bool
    {
        return false;
    }
}
