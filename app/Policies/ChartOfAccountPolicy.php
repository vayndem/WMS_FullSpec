<?php

namespace App\Policies;

use App\Models\ChartOfAccount;
use App\Models\User;

class ChartOfAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAccounting();
    }

    public function view(User $user, ChartOfAccount $chartOfAccount): bool
    {
        return $user->isAccounting();
    }

    public function create(User $user): bool
    {
        return $user->isAccounting();
    }

    public function update(User $user, ChartOfAccount $chartOfAccount): bool
    {
        return $user->isAccounting();
    }

    public function delete(User $user, ChartOfAccount $chartOfAccount): bool
    {
        return $user->isAccounting()
            && !$chartOfAccount->jurnalDetails()->exists()
            && !$chartOfAccount->isMapped();
    }

    public function updateMapping(User $user): bool
    {
        return $user->isAccounting();
    }
}
