<?php

namespace App\Policies;

use App\Models\Lpb;
use App\Models\User;

class LpbPolicy
{
    private function canViewReceipts(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_WAREHOUSE, User::ROLE_ACCOUNTING]);
    }

    private function canManageReceipts(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_WAREHOUSE]);
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewReceipts($user);
    }

    public function view(User $user, Lpb $lpb): bool
    {
        return $this->canViewReceipts($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageReceipts($user);
    }

    public function update(User $user, Lpb $lpb): bool
    {
        return $this->canManageReceipts($user) && (int) $lpb->kunci === 0;
    }

    public function delete(User $user, Lpb $lpb): bool
    {
        return $this->canManageReceipts($user) && (int) $lpb->kunci === 0;
    }
}
