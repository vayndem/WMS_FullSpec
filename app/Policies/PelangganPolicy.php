<?php

namespace App\Policies;

use App\Models\Pelanggan;
use App\Models\User;

class PelangganPolicy
{
    private function penjual(User $user): bool
    {
        return $user->isPurchasing() || $user->isFinance() || $user->isAccounting();
    }

    public function viewAny(User $user): bool
    {
        return $this->penjual($user);
    }

    public function view(User $user, Pelanggan $pelanggan): bool
    {
        return $this->penjual($user);
    }

    public function create(User $user): bool
    {
        return $user->isPurchasing() || $user->isAccounting();
    }

    public function update(User $user, Pelanggan $pelanggan): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Pelanggan $pelanggan): bool
    {
        return $user->isAccounting();
    }
}
