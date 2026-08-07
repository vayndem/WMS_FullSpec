<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StockOpname;

class StockOpnamePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array((int) $user->type, [14, 33], true);
    }

    public function view(User $user, StockOpname $opname): bool
    {
        return in_array((int) $user->type, [14, 33], true);
    }

    public function viewFinancials(User $user): bool
    {
        return (int) $user->type === 33;
    }

    public function create(User $user): bool
    {
        return (int) $user->type === 14;
    }

    public function update(User $user, StockOpname $opname): bool
    {
        return (int) $user->type === 14 && in_array($opname->status, [StockOpname::DRAFT, StockOpname::REJECTED], true);
    }

    public function delete(User $user, StockOpname $opname): bool
    {
        return (int) $user->type === 14 && $opname->status === StockOpname::DRAFT;
    }

    public function submit(User $user, StockOpname $opname): bool
    {
        return (int) $user->type === 14 && in_array($opname->status, [StockOpname::DRAFT, StockOpname::REJECTED], true);
    }

    public function approve(User $user, StockOpname $opname): bool
    {
        return (int) $user->type === 33 && $opname->status === StockOpname::SUBMITTED;
    }

    public function reject(User $user, StockOpname $opname): bool
    {
        return $this->approve($user, $opname);
    }

    public function post(User $user, StockOpname $opname): bool
    {
        return (int) $user->type === 33 && $opname->status === StockOpname::APPROVED;
    }
}
