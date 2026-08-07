<?php

namespace App\Policies;

use App\Models\Debit;
use App\Models\ApiUser;

class DebitPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function view(ApiUser $user, Debit $debit): bool
    {
        return (int) $user->type === 33;
    }

    public function create(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function update(ApiUser $user, Debit $debit): bool
    {
        return (int) $user->type === 33;
    }

    public function delete(ApiUser $user, Debit $debit): bool
    {
        return (int) $user->type === 33;
    }
}
