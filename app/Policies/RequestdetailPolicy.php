<?php

namespace App\Policies;

use App\Models\RequestDetail;
use App\Models\ApiUser;

class RequestDetailPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function view(ApiUser $user, RequestDetail $requestdetail): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function create(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function update(ApiUser $user, RequestDetail $requestdetail): bool
    {
        if (!in_array((int) $user->type, [5, 14, 33], true)) {
            return false;
        }

        return strtolower((string) optional($requestdetail->request)->status) === 'pending';
    }

    public function delete(ApiUser $user, RequestDetail $requestdetail): bool
    {
        if (!in_array((int) $user->type, [5, 14, 33], true)) {
            return false;
        }

        return strtolower((string) optional($requestdetail->request)->status) === 'pending';
    }
}
