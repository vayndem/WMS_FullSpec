<?php

namespace App\Policies;

use App\Models\AccountingReconciliation;
use App\Models\User;

class AccountingReconciliationPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array((int) $user->type, [5, 33], true);
    }

    public function view(User $user, AccountingReconciliation $reconciliation): bool
    {
        return $this->viewAny($user);
    }

    public function viewFinancials(User $user): bool
    {
        return (int) $user->type === 33;
    }
}
