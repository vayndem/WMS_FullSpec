<?php

namespace App\Policies;

use App\Models\PesananPembelian;
use App\Models\User;

class PesananPembelianPolicy
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

    public function view(User $user, PesananPembelian $pembelian): bool
    {
        return $this->canManagePurchases($user);
    }

    public function create(User $user): bool
    {
        return $this->canManagePurchases($user);
    }

    public function update(User $user, PesananPembelian $pembelian): bool
    {
        return $this->canManagePurchases($user);
    }

    public function delete(User $user, PesananPembelian $pembelian): bool
    {
        return $this->canManagePurchases($user);
    }
}
