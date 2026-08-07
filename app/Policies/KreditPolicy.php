<?php

namespace App\Policies;

use App\Models\Kredit;
use App\Models\User;

class KreditPolicy
{
    public function viewAny(User $user): bool
    {
        return (int) $user->type === 33;
    }

    public function view(User $user, Kredit $kredit): bool
    {
        return (int) $user->type === 33;
    }

    public function create(User $user): bool
    {
        return (int) $user->type === 33;
    }

    public function update(User $user, Kredit $kredit): bool
    {
        return (int) $user->type === 33;
    }

    public function delete(User $user, Kredit $kredit): bool
    {
        return (int) $user->type === 33;
    }
}
