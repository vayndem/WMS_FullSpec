<?php

namespace App\Policies;

use App\Models\SuratJalan;
use App\Models\User;

class SuratJalanPolicy
{
    private function pengamat(User $user): bool
    {
        return $user->isPurchasing() || $user->isFinance() || $user->isAccounting() || $user->isWarehouseOperator();
    }

    public function viewAny(User $user): bool
    {
        return $this->pengamat($user);
    }

    public function view(User $user, SuratJalan $suratJalan): bool
    {
        return $this->pengamat($user);
    }

    public function post(User $user, SuratJalan $suratJalan): bool
    {
        return $user->isWarehouseOperator()
            && $suratJalan->isDraft()
            && $user->canAccessGudang((int) $suratJalan->gudang_id, 'npk');
    }

    public function faktur(User $user, SuratJalan $suratJalan): bool
    {
        return ($user->isAccounting() || $user->isFinance()) && $suratJalan->isPosted();
    }

    public function retur(User $user, SuratJalan $suratJalan): bool
    {
        return $user->isAccounting() && $suratJalan->isPosted();
    }
}
