<?php

namespace App\Policies;

use App\Models\JurnalDetail;
use App\Models\User;

class JurnalDetailPolicy
{
    public function viewAny(User $user): bool
    {
        return (int) $user->type === 33;
    }

    public function view(User $user, JurnalDetail $jurnalDetail): bool
    {
        return (int) $user->type === 33;
    }

    public function create(User $user): bool
    {
        return (int) $user->type === 33;
    }

    public function update(User $user, JurnalDetail $jurnalDetail): bool
    {
        return (int) $user->type === 33;
    }

    public function delete(User $user, JurnalDetail $jurnalDetail): bool
    {
        return (int) $user->type === 33;
    }
}
