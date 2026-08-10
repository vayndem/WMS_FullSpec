<?php

namespace App\Policies;

use App\Models\TipePembebanan;
use App\Models\User;

class TipePembebananPolicy
{
    private function canManageAllocationTypes(User $user): bool
    {
        return $user->isAccounting();
    }

    public function viewAny(User $user): bool
    {
        return $this->canManageAllocationTypes($user);
    }

    public function view(User $user, TipePembebanan $tipePembebanan): bool
    {
        return $this->canManageAllocationTypes($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageAllocationTypes($user);
    }

    public function update(User $user, TipePembebanan $tipePembebanan): bool
    {
        return $this->canManageAllocationTypes($user);
    }

    public function delete(User $user, TipePembebanan $tipePembebanan): bool
    {
        return $this->canManageAllocationTypes($user);
    }
}
