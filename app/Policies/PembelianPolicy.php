<?php

namespace App\Policies;

use App\Models\Pembelian;
use App\Models\User;

class PembelianPolicy
{
    public const PPN_RATE = 11.0;

    public function viewAny(User $user): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }

    public function view(User $user, Pembelian $pembelian): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }

    public function create(User $user): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }

    public function update(User $user, Pembelian $pembelian): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }

    public function delete(User $user, Pembelian $pembelian): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }
}
