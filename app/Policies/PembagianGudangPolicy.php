<?php

namespace App\Policies;

use App\Models\PembagianGudang;
use App\Models\User;

class PembagianGudangPolicy
{
    private function ok(User $u): bool
    {
        return $u->isWarehouse();
    }
    public function viewAny(User $u): bool
    {
        return $this->ok($u);
    }
    public function create(User $u): bool
    {
        return $this->ok($u);
    }
    public function update(User $u, PembagianGudang $m): bool
    {
        return $this->ok($u);
    }
    public function delete(User $u, PembagianGudang $m): bool
    {
        return $this->ok($u);
    }
}
