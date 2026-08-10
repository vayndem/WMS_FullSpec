<?php

namespace App\Policies;

use App\Models\Debit;
use App\Models\User;

class DebitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAccounting();
    }

    public function view(User $user, Debit $debit): bool
    {
        return $user->isAccounting();
    }

    public function create(User $user): bool
    {
        return $user->isAccounting();
    }

    public function update(User $user, Debit $debit): bool
    {
        return $user->isAccounting();
    }

    public function delete(User $user, Debit $debit): bool
    {
        return $user->isAccounting();
    }
}
