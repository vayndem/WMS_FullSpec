<?php

namespace App\Policies;

use App\Models\TipePembebanan;
use App\Models\User;

class TipePembebananPolicy
{
    public function viewAny(User $user): bool
    {
        return (int) $user->type === 33;
    }

    public function view(User $user, TipePembebanan $tipePembebanan): bool
    {
        return (int) $user->type === 33;
    }

    public function create(User $user): bool
    {
        return (int) $user->type === 33;
    }

    public function update(User $user, TipePembebanan $tipePembebanan): bool
    {
        return (int) $user->type === 33;
    }

    public function delete(User $user, TipePembebanan $tipePembebanan): bool
    {
        return (int) $user->type === 33;
    }
}
