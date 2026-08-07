<?php

namespace App\Policies;

use App\Models\ApiUser;
use App\Models\StockOpname;

class StockOpnamePolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return in_array((int) $user->type, [14, 33], true);
    }

    public function view(ApiUser $user, StockOpname $opname): bool
    {
        return in_array((int) $user->type, [14, 33], true);
    }

    public function viewFinancials(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function create(ApiUser $user): bool
    {
        return (int) $user->type === 14;
    }

    public function update(ApiUser $user, StockOpname $opname): bool
    {
        return (int) $user->type === 14 && in_array($opname->status, [StockOpname::DRAFT, StockOpname::REJECTED], true);
    }

    public function delete(ApiUser $user, StockOpname $opname): bool
    {
        return (int) $user->type === 14 && $opname->status === StockOpname::DRAFT;
    }

    public function submit(ApiUser $user, StockOpname $opname): bool
    {
        return (int) $user->type === 14 && in_array($opname->status, [StockOpname::DRAFT, StockOpname::REJECTED], true);
    }

    public function approve(ApiUser $user, StockOpname $opname): bool
    {
        return (int) $user->type === 33 && $opname->status === StockOpname::SUBMITTED;
    }

    public function reject(ApiUser $user, StockOpname $opname): bool
    {
        return $this->approve($user, $opname);
    }

    public function post(ApiUser $user, StockOpname $opname): bool
    {
        return (int) $user->type === 33 && $opname->status === StockOpname::APPROVED;
    }
}
