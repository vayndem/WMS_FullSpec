<?php

namespace App\Policies;

use App\Models\ApiUser;
use App\Models\LpbDetail;

class LpbdetailPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return (int) $user->type === 5;
    }

    public function view(ApiUser $user, LpbDetail $lpbdetail): bool
    {
        return (int) $user->type === 5;
    }

    public function create(ApiUser $user): bool
    {
        return (int) $user->type === 5;
    }

    public function update(ApiUser $user, LpbDetail $lpbdetail): bool
    {
        return (int) $user->type === 5 && (int) ($lpbdetail->lpb->kunci ?? 0) === 0;
    }

    public function delete(ApiUser $user, LpbDetail $lpbdetail): bool
    {
        return (int) $user->type === 5 && (int) ($lpbdetail->lpb->kunci ?? 0) === 0;
    }
}
