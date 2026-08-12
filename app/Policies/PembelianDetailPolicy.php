<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PembelianDetail;

class PembelianDetailPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }

    public function view(User $user, PembelianDetail $pembeliandetail): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }

    public function update(User $user, PembelianDetail $pembeliandetail): bool
    {
        if (!$user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING])) {
            return false;
        }

        return (int) $pembeliandetail->pembelian->kunci === 0;
    }

    public function delete(User $user, PembelianDetail $pembeliandetail): bool
    {
        if (!$user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING])) {
            return false;
        }

        return (int) $pembeliandetail->pembelian->kunci === 0;
    }
}
