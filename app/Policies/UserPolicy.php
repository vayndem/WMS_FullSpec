<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    private function dirinyaSendiri(User $user, User $target): bool
    {
        return $user->id === $target->id;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, User $target): bool
    {
        return $this->dirinyaSendiri($user, $target);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $target): bool
    {
        return false;
    }

    public function deactivate(User $user, User $target): bool
    {
        return false;
    }

    public function resetPassword(User $user, User $target): bool
    {
        return false;
    }
}
