<?php

namespace App\Policies;

use App\Models\AccountingPeriodLock;
use App\Models\User;

class AccountingPeriodLockPolicy
{
    public function viewAny(User $user): bool
    {
        return (int) $user->type === 33;
    }
    public function view(User $user, AccountingPeriodLock $lock): bool
    {
        return (int) $user->type === 33;
    }
    public function create(User $user): bool
    {
        return (int) $user->type === 33;
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
        return (int) $user->type === 33 && $lock->isLocked();
    }
}
