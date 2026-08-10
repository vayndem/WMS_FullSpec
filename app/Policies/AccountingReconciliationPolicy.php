<?php

namespace App\Policies;

use App\Models\AccountingReconciliation;
use App\Models\User;

class AccountingReconciliationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }

    public function view(User $user, AccountingReconciliation $reconciliation): bool
    {
        return $this->viewAny($user);
    }

    public function viewFinancials(User $user): bool
    {
        return $user->isAccounting();
    }
}
