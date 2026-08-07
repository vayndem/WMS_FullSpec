<?php

namespace App\Policies;

use App\Models\AccountingPeriodLock;
use App\Models\ApiUser;

class AccountingPeriodLockPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }
    public function view(ApiUser $user, AccountingPeriodLock $lock): bool
    {
        return (int) $user->type === 33;
    }
    public function create(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }
    public function update(ApiUser $user, AccountingPeriodLock $lock): bool
    {
        return false;
    }
    public function delete(ApiUser $user, AccountingPeriodLock $lock): bool
    {
        return false;
    }
    public function unlock(ApiUser $user, AccountingPeriodLock $lock): bool
    {
        return (int) $user->type === 33 && $lock->isLocked();
    }
}
