<?php

namespace App\Policies;

use App\Models\Gudang;
use App\Models\User;

class GudangPolicy
{
    private function admin(User $user): bool
    {
        return $user->isWarehouse();
    }

    public function viewAny(User $user): bool
    {
        return $this->admin($user);
    }

    public function view(User $user, Gudang $gudang): bool
    {
        return $this->admin($user);
    }

    public function create(User $user): bool
    {
        return $this->admin($user);
    }

    public function update(User $user, Gudang $gudang): bool
    {
        return $this->admin($user);
    }

    public function delete(User $user, Gudang $gudang): bool
    {
        return false;
    }
}
