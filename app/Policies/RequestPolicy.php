<?php

namespace App\Policies;

use App\Models\Request as RequestModel;
use App\Models\ApiUser;

class RequestPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function create(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 14], true);
    }

    public function view(ApiUser $user, RequestModel $requestModel): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function approve(ApiUser $user, RequestModel $requestModel): bool
    {
        return in_array((int) $user->type, [5, 33]) && $requestModel->status === 'pending';
    }
}
