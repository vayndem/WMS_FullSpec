<?php

namespace App\Policies;

use App\Models\ChartOfAccount;
use App\Models\User;

class ChartOfAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return (int) $user->type === 33;
    }

    public function view(User $user, ChartOfAccount $chartOfAccount): bool
    {
        return (int) $user->type === 33;
    }

    public function create(User $user): bool
    {
        return (int) $user->type === 33;
    }

    public function update(User $user, ChartOfAccount $chartOfAccount): bool
    {
        return (int) $user->type === 33;
    }

    public function delete(User $user, ChartOfAccount $chartOfAccount): bool
    {
        return (int) $user->type === 33
            && !$chartOfAccount->jurnalDetails()->exists()
            && !$chartOfAccount->isMapped();
    }

    public function updateMapping(User $user): bool
    {
        return (int) $user->type === 33;
    }
}
