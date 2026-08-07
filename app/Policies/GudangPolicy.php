<?php

namespace App\Policies;

use App\Models\Gudang;
use App\Models\User;

class GudangPolicy
{
    private function admin(User $u): bool
    {
        return (int)$u->type === 14;
    }
    public function viewAny(User $u): bool
    {
        return $this->admin($u);
    }
    public function view(User $u, Gudang $m): bool
    {
        return $this->admin($u);
    }
    public function create(User $u): bool
    {
        return $this->admin($u);
    }
    public function update(User $u, Gudang $m): bool
    {
        return $this->admin($u);
    }
    public function delete(User $u, Gudang $m): bool
    {
        return false;
    }
}
