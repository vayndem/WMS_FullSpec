<?php

namespace App\Policies;

use App\Models\PusatKerja;
use App\Models\User;

class PusatKerjaPolicy
{
    private function pengamat(User $user): bool
    {
        return $user->isProduction()
            || $user->isWarehouseOperator()
            || $user->isPurchasing()
            || $user->isAccounting();
    }

    private function pengelola(User $user): bool
    {
        return $user->isProduction() || $user->isAccounting();
    }

    public function viewAny(User $user): bool
    {
        return $this->pengamat($user);
    }

    public function view(User $user, PusatKerja $pusatKerja): bool
    {
        return $this->pengamat($user);
    }

    public function create(User $user): bool
    {
        return $this->pengelola($user);
    }

    public function update(User $user, PusatKerja $pusatKerja): bool
    {
        return $this->pengelola($user);
    }
}
