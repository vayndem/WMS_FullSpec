<?php

namespace App\Policies;

use App\Models\MutasiStok;
use App\Models\User;

class MutasiStokPolicy
{
    public function viewAny(User $u): bool
    {
        return (int)$u->type === 14;
    }
    public function view(User $u, MutasiStok $m): bool
    {
        return (int)$u->type === 14;
    }
}
