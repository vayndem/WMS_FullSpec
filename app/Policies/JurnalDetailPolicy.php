<?php

namespace App\Policies;

use App\Models\JurnalDetail;
use App\Models\User;

class JurnalDetailPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAccounting();
    }

    public function view(User $user, JurnalDetail $jurnalDetail): bool
    {
        return $user->isAccounting();
    }

    public function create(User $user): bool
    {
        return $user->isAccounting();
    }

    public function update(User $user, JurnalDetail $jurnalDetail): bool
    {
        return $user->isAccounting();
    }

    public function delete(User $user, JurnalDetail $jurnalDetail): bool
    {
        return $user->isAccounting();
    }
}
