<?php

namespace App\Policies;

use App\Models\FakturPenjualan;
use App\Models\User;

class FakturPenjualanPolicy
{
    private function pengamat(User $user): bool
    {
        return $user->isPurchasing() || $user->isFinance() || $user->isAccounting() || $user->isAccountingManager();
    }

    public function viewAny(User $user): bool
    {
        return $this->pengamat($user);
    }

    public function view(User $user, FakturPenjualan $faktur): bool
    {
        return $this->pengamat($user);
    }

    public function post(User $user, FakturPenjualan $faktur): bool
    {
        return $user->isAccounting() && $faktur->isDraft();
    }

    public function delete(User $user, FakturPenjualan $faktur): bool
    {
        return $user->isAccounting() && $faktur->isDraft();
    }

    public function terimaPembayaran(User $user, FakturPenjualan $faktur): bool
    {
        return $user->isFinance() && $faktur->isTertagih();
    }
}
