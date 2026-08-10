<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StockOpname;

class StockOpnamePolicy
{
    private function canViewOpname(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_WAREHOUSE, User::ROLE_ACCOUNTING, User::ROLE_PRODUCTION]);
    }

    private function canViewOpnameFinancials(User $user): bool
    {
        return $user->isAccounting();
    }

    private function canPrepareOpname(User $user): bool
    {
        return $user->isWarehouseOperator();
    }

    private function canApproveOpname(User $user): bool
    {
        return $user->isAccounting();
    }

    private function canAccessOpnameWarehouse(User $user, StockOpname $opname): bool
    {
        if (!$user->isProduction()) {
            return true;
        }

        return $user->canAccessGudang((int) $opname->warehouse_id, 'opname');
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewOpname($user);
    }

    public function view(User $user, StockOpname $opname): bool
    {
        return $this->canViewOpname($user) && $this->canAccessOpnameWarehouse($user, $opname);
    }

    public function viewFinancials(User $user): bool
    {
        return $this->canViewOpnameFinancials($user);
    }

    public function create(User $user): bool
    {
        return $this->canPrepareOpname($user);
    }

    public function update(User $user, StockOpname $opname): bool
    {
        return $this->canPrepareOpname($user)
            && $this->canAccessOpnameWarehouse($user, $opname)
            && in_array($opname->status, [StockOpname::DRAFT, StockOpname::REJECTED], true);
    }

    public function delete(User $user, StockOpname $opname): bool
    {
        return $this->canPrepareOpname($user)
            && $this->canAccessOpnameWarehouse($user, $opname)
            && $opname->status === StockOpname::DRAFT;
    }

    public function submit(User $user, StockOpname $opname): bool
    {
        return $this->canPrepareOpname($user)
            && $this->canAccessOpnameWarehouse($user, $opname)
            && in_array($opname->status, [StockOpname::DRAFT, StockOpname::REJECTED], true);
    }

    public function approve(User $user, StockOpname $opname): bool
    {
        return $this->canApproveOpname($user) && $opname->status === StockOpname::SUBMITTED;
    }

    public function reject(User $user, StockOpname $opname): bool
    {
        return $this->approve($user, $opname);
    }

    public function post(User $user, StockOpname $opname): bool
    {
        return $this->canApproveOpname($user) && $opname->status === StockOpname::APPROVED;
    }
}
