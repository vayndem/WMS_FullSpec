<?php

namespace App\Policies;

use App\Models\KategoriBahan;
use App\Models\User;

class KategoriBahanPolicy
{
    public function viewAny(User $user): bool
    {
        return (int) $user->type === 33;
    }

    public function view(User $user, KategoriBahan $kategoriBahan): bool
    {
        return (int) $user->type === 33;
    }

    public function create(User $user): bool
    {
        return (int) $user->type === 33;
    }

    public function update(User $user, KategoriBahan $kategoriBahan): bool
    {
        return (int) $user->type === 33;
    }

    public function delete(User $user, KategoriBahan $kategoriBahan): bool
    {
        return (int) $user->type === 33
            && !$kategoriBahan->bahan()->exists()
            && !$kategoriBahan->bahanByType()->exists()
            && !$kategoriBahan->lpbDetails()->exists();
    }
}
