<?php

namespace App\Policies;

use App\Models\ReturPembelian;
use App\Models\User;

class ReturPembelianPolicy
{
    private function canViewReturns(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_WAREHOUSE, User::ROLE_ACCOUNTING]);
    }

    private function canManageReturns(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_WAREHOUSE]);
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewReturns($user);
    }

    public function view(User $user, ReturPembelian $returPembelian): bool
    {
        return $this->canViewReturns($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageReturns($user);
    }
}
