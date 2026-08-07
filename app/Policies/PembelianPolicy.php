<?php

namespace App\Policies;

use App\Models\Pembelian;
use App\Models\ApiUser;

class PembelianPolicy
{
    public const PPN_RATE = 11.0;

    public function viewAny(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }

    public function view(ApiUser $user, Pembelian $pembelian): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }

    public function create(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }

    public function update(ApiUser $user, Pembelian $pembelian): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }

    public function delete(ApiUser $user, Pembelian $pembelian): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }
}
