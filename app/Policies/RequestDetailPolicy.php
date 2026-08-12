<?php

namespace App\Policies;

use App\Models\RequestDetail;
use App\Models\User;

class RequestDetailPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_WAREHOUSE, User::ROLE_ACCOUNTING]);
    }

    public function view(User $user, RequestDetail $requestdetail): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_WAREHOUSE, User::ROLE_ACCOUNTING]);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_WAREHOUSE, User::ROLE_ACCOUNTING]);
    }

    public function update(User $user, RequestDetail $requestdetail): bool
    {
        if (!$user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_WAREHOUSE, User::ROLE_ACCOUNTING])) {
            return false;
        }

        return optional($requestdetail->request)->status === \App\Models\MaterialRequest::PENDING;
    }

    public function delete(User $user, RequestDetail $requestdetail): bool
    {
        if (!$user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_WAREHOUSE, User::ROLE_ACCOUNTING])) {
            return false;
        }

        return optional($requestdetail->request)->status === \App\Models\MaterialRequest::PENDING;
    }
}
