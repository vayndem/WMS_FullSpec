<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Pembeliandetail;

class PembeliandetailPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }

    public function view(User $user, Pembeliandetail $pembeliandetail): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }

    public function create(User $user): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }

    public function update(User $user, Pembeliandetail $pembeliandetail): bool
    {
        if (!in_array((int) $user->type, [5, 33])) {
            return false;
        }

        return (int) $pembeliandetail->pembelian->kunci === 0;
    }

    public function delete(User $user, Pembeliandetail $pembeliandetail): bool
    {
        if (!in_array((int) $user->type, [5, 33])) {
            return false;
        }

        return (int) $pembeliandetail->pembelian->kunci === 0;
    }
}
