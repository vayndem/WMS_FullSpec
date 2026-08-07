<?php

namespace App\Policies;

use App\Models\ApiUser;
use App\Models\Npk;

class NpkPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function view(ApiUser $user, Npk $npk): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function create(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function update(ApiUser $user, Npk $npk): bool
    {
        if (!in_array((int) $user->type, [5, 14, 33], true)) {
            return false;
        }

        return (int) $npk->close === 0 && $npk->status_posting !== 'POSTED';
    }

    public function delete(ApiUser $user, Npk $npk): bool
    {
        if (!in_array((int) $user->type, [5, 14, 33], true)) {
            return false;
        }

        return (int) $npk->close === 0 && $npk->status_posting !== 'POSTED';
    }

    public function viewFinancials(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 33], true);
    }
}
