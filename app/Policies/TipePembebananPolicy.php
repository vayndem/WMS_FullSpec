<?php

namespace App\Policies;

use App\Models\TipePembebanan;
use App\Models\ApiUser;

class TipePembebananPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function view(ApiUser $user, TipePembebanan $tipePembebanan): bool
    {
        return (int) $user->type === 33;
    }

    public function create(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function update(ApiUser $user, TipePembebanan $tipePembebanan): bool
    {
        return (int) $user->type === 33;
    }

    public function delete(ApiUser $user, TipePembebanan $tipePembebanan): bool
    {
        return (int) $user->type === 33;
    }
}
