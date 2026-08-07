<?php

namespace App\Policies;

use App\Models\StokGudang;
use App\Models\User;

class StokGudangPolicy
{
    public function viewAny(User $u): bool
    {
        return (int)$u->type === 14;
    }
    public function view(User $u, StokGudang $m): bool
    {
        return (int)$u->type === 14;
    }
}
