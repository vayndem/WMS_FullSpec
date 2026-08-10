<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    private function canViewSuppliers(?User $user): bool
    {
        return true;
    }

    private function canManageSuppliers(User $user): bool
    {
        return $user->isPurchasing();
    }

    public function viewAny(?User $user): bool
    {
        return $this->canViewSuppliers($user);
    }

    public function view(?User $user, Supplier $supplier): bool
    {
        return $this->canViewSuppliers($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageSuppliers($user);
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $this->canManageSuppliers($user);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $this->canManageSuppliers($user);
    }

    public function restore(User $user, Supplier $supplier): bool
    {
        return $this->canManageSuppliers($user);
    }

    public function forceDelete(User $user, Supplier $supplier): bool
    {
        return $this->canManageSuppliers($user);
    }
}
