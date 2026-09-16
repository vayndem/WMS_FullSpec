<?php

namespace App\Policies;

use App\Models\PermintaanPersetujuan;
use App\Models\User;

class PermintaanPersetujuanPolicy
{
    private function pengamat(User $user): bool
    {
        return $user->isAccounting() || $user->isAccountingManager();
    }

    public function viewAny(User $user): bool
    {
        return $this->pengamat($user);
    }

    public function view(User $user, PermintaanPersetujuan $permintaan): bool
    {
        return $this->pengamat($user);
    }

    public function decide(User $user, PermintaanPersetujuan $permintaan): bool
    {
        return $user->isAccountingManager() && $permintaan->isPending();
    }
}
