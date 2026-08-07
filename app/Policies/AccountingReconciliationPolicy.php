<?php

namespace App\Policies;

use App\Models\AccountingReconciliation;
use App\Models\ApiUser;

class AccountingReconciliationPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 33], true);
    }

    public function view(ApiUser $user, AccountingReconciliation $reconciliation): bool
    {
        return $this->viewAny($user);
    }

    public function viewFinancials(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }
}
