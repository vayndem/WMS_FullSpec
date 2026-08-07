<?php

namespace App\Policies;

use App\Models\Lpb;
use App\Models\User;

class LpbPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function view(User $user, Lpb $lpb): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function create(User $user): bool
    {
        return in_array((int) $user->type, [5, 14], true);
    }

    public function update(User $user, Lpb $lpb): bool
    {
        return in_array((int) $user->type, [5, 14], true) && (int) $lpb->kunci === 0;
    }

    public function delete(User $user, Lpb $lpb): bool
    {
        return in_array((int) $user->type, [5, 14], true) && (int) $lpb->kunci === 0;
    }
}
