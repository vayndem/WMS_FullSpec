<?php

namespace App\Policies;

use App\Models\Debit;
use App\Models\User;

class DebitPolicy
{
    public function viewAny(User $user): bool
    {
        return (int) $user->type === 33;
    }

    public function view(User $user, Debit $debit): bool
    {
        return (int) $user->type === 33;
    }

    public function create(User $user): bool
    {
        return (int) $user->type === 33;
    }

    public function update(User $user, Debit $debit): bool
    {
        return (int) $user->type === 33;
    }

    public function delete(User $user, Debit $debit): bool
    {
        return (int) $user->type === 33;
    }
}
