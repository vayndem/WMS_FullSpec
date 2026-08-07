<?php

namespace App\Policies;

use App\Models\JurnalDetail;
use App\Models\ApiUser;

class JurnalDetailPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function view(ApiUser $user, JurnalDetail $jurnalDetail): bool
    {
        return (int) $user->type === 33;
    }

    public function create(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function update(ApiUser $user, JurnalDetail $jurnalDetail): bool
    {
        return (int) $user->type === 33;
    }

    public function delete(ApiUser $user, JurnalDetail $jurnalDetail): bool
    {
        return (int) $user->type === 33;
    }
}
