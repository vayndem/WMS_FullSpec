<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PesananJasa;

class PesananJasaPolicy
{
    private function canViewPesananJasa(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewPesananJasa($user);
    }

    public function view(User $user, PesananJasa $po): bool
    {
        return $this->canViewPesananJasa($user) && $po->document_type === 'SERVICE';
    }

    public function create(User $user): bool
    {
        return $this->canViewPesananJasa($user);
    }

    public function viewFinancials(User $user): bool
    {
        return $this->canViewPesananJasa($user);
    }

    public function update(User $user, PesananJasa $po): bool
    {
        return $this->view($user, $po) && !$po->lpbs()->exists();
    }

    public function delete(User $user, PesananJasa $po): bool
    {
        return $this->update($user, $po);
    }
}
