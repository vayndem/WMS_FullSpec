<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PesananPembelianDetail;

class PesananPembelianDetailPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }

    public function view(User $user, PesananPembelianDetail $pembeliandetail): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }

    public function update(User $user, PesananPembelianDetail $pembeliandetail): bool
    {
        if (!$user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING])) {
            return false;
        }

        return (int) $pembeliandetail->pembelian->kunci === 0;
    }

    public function delete(User $user, PesananPembelianDetail $pembeliandetail): bool
    {
        if (!$user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING])) {
            return false;
        }

        return (int) $pembeliandetail->pembelian->kunci === 0;
    }
}
