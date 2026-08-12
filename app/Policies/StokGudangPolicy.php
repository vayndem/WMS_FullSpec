<?php

namespace App\Policies;

use App\Models\StokGudang;
use App\Models\User;

class StokGudangPolicy
{
    private function ok(User $u): bool
    {
        return $u->isWarehouseOperator();
    }

    private function canAccess(User $u, StokGudang $m): bool
    {
        if (!$u->isProduction()) {
            return true;
        }

        return $u->canAccessGudang((int) $m->gudang_id);
    }

    public function viewAny(User $u): bool
    {
        return $this->ok($u);
    }
    public function view(User $u, StokGudang $m): bool
    {
        return $this->ok($u) && $this->canAccess($u, $m);
    }
    public function reconcile(User $u): bool
    {
        return $u->isWarehouseOperator() || $u->isAccounting();
    }
}
