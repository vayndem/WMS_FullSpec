<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PemakaianBarang;

class PemakaianBarangPolicy
{
    private function canViewNpk(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_WAREHOUSE, User::ROLE_ACCOUNTING, User::ROLE_PRODUCTION]);
    }

    private function canViewNpkFinancials(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }

    private function canEditNpk(PemakaianBarang $npk): bool
    {
        return $npk->status === PemakaianBarang::DRAFT;
    }

    private function canAccessNpkWarehouse(User $user, PemakaianBarang $npk): bool
    {
        if (!$user->isProduction()) {
            return true;
        }

        return $user->canAccessGudang((int) $npk->id_gudang_asal, 'npk');
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewNpk($user);
    }

    public function view(User $user, PemakaianBarang $npk): bool
    {
        return $this->canViewNpk($user) && $this->canAccessNpkWarehouse($user, $npk);
    }

    public function create(User $user): bool
    {
        return $this->canViewNpk($user);
    }

    public function update(User $user, PemakaianBarang $npk): bool
    {
        if (!$this->canViewNpk($user) || !$this->canAccessNpkWarehouse($user, $npk)) {
            return false;
        }

        return $this->canEditNpk($npk);
    }

    public function delete(User $user, PemakaianBarang $npk): bool
    {
        if (!$this->canViewNpk($user) || !$this->canAccessNpkWarehouse($user, $npk)) {
            return false;
        }

        return $this->canEditNpk($npk);
    }

    public function viewFinancials(User $user): bool
    {
        return $this->canViewNpkFinancials($user);
    }
}
