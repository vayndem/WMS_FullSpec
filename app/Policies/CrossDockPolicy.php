<?php

namespace App\Policies;

use App\Models\CrossDock;
use App\Models\User;

class CrossDockPolicy
{
    private function operator(User $user): bool
    {
        return $user->isWarehouseOperator();
    }

    private function bolehGudang(User $user, CrossDock $crossDock): bool
    {
        return $user->canAccessGudang((int) $crossDock->gudang_id, 'receive');
    }

    public function viewAny(User $user): bool
    {
        return $this->operator($user);
    }

    public function view(User $user, CrossDock $crossDock): bool
    {
        return $this->operator($user) && $this->bolehGudang($user, $crossDock);
    }

    public function create(User $user): bool
    {
        return $this->operator($user);
    }

    public function batalkan(User $user, CrossDock $crossDock): bool
    {
        return $this->operator($user)
            && $this->bolehGudang($user, $crossDock)
            && $crossDock->isAktif();
    }
}
