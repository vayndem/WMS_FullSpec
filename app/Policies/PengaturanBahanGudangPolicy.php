<?php

namespace App\Policies;

use App\Models\PengaturanBahanGudang;
use App\Models\User;

class PengaturanBahanGudangPolicy
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
    public function update(User $u, PengaturanBahanGudang $m): bool
    {
        return $this->ok($u);
    }
    public function delete(User $u, PengaturanBahanGudang $m): bool
    {
        return $this->ok($u);
    }
}
