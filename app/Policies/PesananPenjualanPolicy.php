<?php

namespace App\Policies;

use App\Models\PesananPenjualan;
use App\Models\User;

class PesananPenjualanPolicy
{
    private function pengamat(User $user): bool
    {
        return $user->isPurchasing() || $user->isFinance() || $user->isAccounting() || $user->isWarehouseOperator();
    }

    public function viewAny(User $user): bool
    {
        return $this->pengamat($user);
    }

    public function view(User $user, PesananPenjualan $pesanan): bool
    {
        return $this->pengamat($user);
    }

    public function create(User $user): bool
    {
        return $user->isPurchasing() || $user->isAccounting();
    }

    public function kirim(User $user, PesananPenjualan $pesanan): bool
    {
        return ($user->isWarehouseOperator() || $user->isPurchasing()) && $pesanan->isOpen();
    }
}
