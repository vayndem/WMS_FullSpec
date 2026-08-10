<?php

namespace App\Policies;

use App\Models\Kredit;
use App\Models\User;

class KreditPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAccounting();
    }

    public function view(User $user, Kredit $kredit): bool
    {
        return $user->isAccounting();
    }

    public function create(User $user): bool
    {
        return $user->isAccounting();
    }

    public function update(User $user, Kredit $kredit): bool
    {
        return $user->isAccounting();
    }

    public function delete(User $user, Kredit $kredit): bool
    {
        return $user->isAccounting();
    }
}
