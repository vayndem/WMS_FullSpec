<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LpbDetail;

class LpbdetailPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPurchasing();
    }

    public function view(User $user, LpbDetail $lpbdetail): bool
    {
        return $user->isPurchasing();
    }

    public function create(User $user): bool
    {
        return $user->isPurchasing();
    }

    public function update(User $user, LpbDetail $lpbdetail): bool
    {
        return $user->isPurchasing() && (int) ($lpbdetail->lpb->kunci ?? 0) === 0;
    }

    public function delete(User $user, LpbDetail $lpbdetail): bool
    {
        return $user->isPurchasing() && (int) ($lpbdetail->lpb->kunci ?? 0) === 0;
    }
}
