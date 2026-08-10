<?php

namespace App\Policies;

use App\Models\Pembelian;
use App\Models\User;

class PembelianPolicy
{
    public const PPN_RATE = 11.0;

    private function canManagePurchases(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }

    public function viewAny(User $user): bool
    {
        return $this->canManagePurchases($user);
    }

    public function view(User $user, Pembelian $pembelian): bool
    {
        return $this->canManagePurchases($user);
    }

    public function create(User $user): bool
    {
        return $this->canManagePurchases($user);
    }

    public function update(User $user, Pembelian $pembelian): bool
    {
        return $this->canManagePurchases($user);
    }

    public function delete(User $user, Pembelian $pembelian): bool
    {
        return $this->canManagePurchases($user);
    }
}
