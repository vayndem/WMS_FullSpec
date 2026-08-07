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
        return (int) $user->type === 33;
    }

    public function create(User $user): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function update(User $user, Bahan $bahan): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function delete(User $user, Bahan $bahan): bool
    {
        return false;
    }
}
