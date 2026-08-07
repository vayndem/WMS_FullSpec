<?php

namespace App\Policies;

use App\Models\Request as RequestModel;
use App\Models\User;

class RequestPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function create(User $user): bool
    {
        return in_array((int) $user->type, [5, 14], true);
    }

    public function view(User $user, RequestModel $requestModel): bool
    {
        return in_array((int) $user->type, [5, 14, 33], true);
    }

    public function approve(User $user, RequestModel $requestModel): bool
    {
        return in_array((int) $user->type, [5, 33]) && $requestModel->status === 'pending';
    }
}
