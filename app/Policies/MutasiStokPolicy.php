<?php

namespace App\Policies;

use App\Models\MutasiStok;
use App\Models\User;

class MutasiStokPolicy
{
    private function ok(User $u): bool
    {
        return $u->isWarehouseOperator();
    }

    private function canAccess(User $u, MutasiStok $m): bool
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
    public function view(User $u, MutasiStok $m): bool
    {
        return $this->ok($u) && $this->canAccess($u, $m);
    }
}
