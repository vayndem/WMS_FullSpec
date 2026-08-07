<?php

namespace App\Policies;

use App\Models\Kredit;
use App\Models\ApiUser;

class KreditPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function view(ApiUser $user, Kredit $kredit): bool
    {
        return (int) $user->type === 33;
    }

    public function create(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function update(ApiUser $user, Kredit $kredit): bool
    {
        return (int) $user->type === 33;
    }

    public function delete(ApiUser $user, Kredit $kredit): bool
    {
        return (int) $user->type === 33;
    }
}
