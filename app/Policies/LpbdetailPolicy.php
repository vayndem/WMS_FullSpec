<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LpbDetail;

class LpbdetailPolicy
{
    public function viewAny(User $user): bool
    {
        return (int) $user->type === 5;
    }

    public function view(User $user, LpbDetail $lpbdetail): bool
    {
        return (int) $user->type === 5;
    }

    public function create(User $user): bool
    {
        return (int) $user->type === 5;
    }

    public function update(User $user, LpbDetail $lpbdetail): bool
    {
        return (int) $user->type === 5 && (int) ($lpbdetail->lpb->kunci ?? 0) === 0;
    }

    public function delete(User $user, LpbDetail $lpbdetail): bool
    {
        return (int) $user->type === 5 && (int) ($lpbdetail->lpb->kunci ?? 0) === 0;
    }
}
