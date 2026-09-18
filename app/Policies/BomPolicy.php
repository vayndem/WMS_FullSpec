<?php

namespace App\Policies;

use App\Models\Bom;
use App\Models\User;

class BomPolicy
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

    public function view(User $user, Bom $bom): bool
    {
        return $this->pengamat($user);
    }

    public function create(User $user): bool
    {
        return $this->pengelola($user);
    }

    public function update(User $user, Bom $bom): bool
    {
        return $this->pengelola($user);
    }

    public function delete(User $user, Bom $bom): bool
    {
        return $this->pengelola($user) && !$bom->isAktif();
    }
}
