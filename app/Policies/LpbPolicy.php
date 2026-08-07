<?php

namespace App\Policies;

use App\Models\Lpb;
use App\Models\ApiUser;

class LpbPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function view(ApiUser $user, Lpb $lpb): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function create(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 14], true);
    }

    public function update(ApiUser $user, Lpb $lpb): bool
    {
        return in_array((int) $user->type, [5, 14], true) && (int) $lpb->kunci === 0;
    }

    public function delete(ApiUser $user, Lpb $lpb): bool
    {
        return in_array((int) $user->type, [5, 14], true) && (int) $lpb->kunci === 0;
    }
}
