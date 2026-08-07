<?php

namespace App\Policies;

use App\Models\ApiUser;
use App\Models\Pembeliandetail;

class PembeliandetailPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }

    public function view(ApiUser $user, Pembeliandetail $pembeliandetail): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }

    public function create(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 33]);
    }

    public function update(ApiUser $user, Pembeliandetail $pembeliandetail): bool
    {
        if (!in_array((int) $user->type, [5, 33])) {
            return false;
        }

        return (int) $pembeliandetail->pembelian->kunci === 0;
    }

    public function delete(ApiUser $user, Pembeliandetail $pembeliandetail): bool
    {
        if (!in_array((int) $user->type, [5, 33])) {
            return false;
        }

        return (int) $pembeliandetail->pembelian->kunci === 0;
    }
}
