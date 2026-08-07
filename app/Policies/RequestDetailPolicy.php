<?php

namespace App\Policies;

use App\Models\RequestDetail;
use App\Models\User;

class RequestDetailPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function view(User $user, RequestDetail $requestdetail): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function create(User $user): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function update(User $user, RequestDetail $requestdetail): bool
    {
        if (!in_array((int) $user->type, [5, 14, 33], true)) {
            return false;
        }

        return strtolower((string) optional($requestdetail->request)->status) === 'pending';
    }

    public function delete(User $user, RequestDetail $requestdetail): bool
    {
        if (!in_array((int) $user->type, [5, 14, 33], true)) {
            return false;
        }

        return strtolower((string) optional($requestdetail->request)->status) === 'pending';
    }
}
