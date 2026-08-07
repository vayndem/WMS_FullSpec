<?php

namespace App\Policies;

use App\Models\ChartOfAccount;
use App\Models\ApiUser;

class ChartOfAccountPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function view(ApiUser $user, ChartOfAccount $chartOfAccount): bool
    {
        return (int) $user->type === 33;
    }

    public function create(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function update(ApiUser $user, ChartOfAccount $chartOfAccount): bool
    {
        return (int) $user->type === 33;
    }

    public function delete(ApiUser $user, ChartOfAccount $chartOfAccount): bool
    {
        return (int) $user->type === 33
            && !$chartOfAccount->jurnalDetails()->exists()
            && !$chartOfAccount->isMapped();
    }

    public function updateMapping(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }
}
