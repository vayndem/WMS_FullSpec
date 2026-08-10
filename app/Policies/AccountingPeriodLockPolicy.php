<?php

namespace App\Policies;

use App\Models\AccountingPeriodLock;
use App\Models\User;

class AccountingPeriodLockPolicy
{
    private function canManagePeriods(User $user): bool
    {
        return $user->isAccounting();
    }

    public function viewAny(User $user): bool
    {
        return $this->canManagePeriods($user);
    }

    public function view(User $user, AccountingPeriodLock $lock): bool
    {
        return $this->canManagePeriods($user);
    }

    public function create(User $user): bool
    {
        return $this->canManagePeriods($user);
    }

    public function update(User $user, AccountingPeriodLock $lock): bool
    {
        return false;
    }

    public function delete(User $user, AccountingPeriodLock $lock): bool
    {
        return false;
    }

    public function unlock(User $user, AccountingPeriodLock $lock): bool
    {
        return $this->canManagePeriods($user) && $lock->isLocked();
    }
}
