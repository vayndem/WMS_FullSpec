<?php

namespace App\Policies;

use App\Models\Request as RequestModel;
use App\Models\User;

class RequestPolicy
{
    private function canViewRequests(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_WAREHOUSE, User::ROLE_ACCOUNTING]);
    }

    private function canCreateRequests(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_WAREHOUSE]);
    }

    private function canApproveRequests(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewRequests($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreateRequests($user);
    }

    public function view(User $user, RequestModel $requestModel): bool
    {
        return $this->canViewRequests($user);
    }

    public function approve(User $user, RequestModel $requestModel): bool
    {
        return $this->canApproveRequests($user) && $requestModel->status === 'pending';
    }
}
