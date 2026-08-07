<?php

namespace App\Policies;

use App\Models\KategoriBahan;
use App\Models\ApiUser;

class KategoriBahanPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function view(ApiUser $user, KategoriBahan $kategoriBahan): bool
    {
        return (int) $user->type === 33;
    }

    public function create(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function update(ApiUser $user, KategoriBahan $kategoriBahan): bool
    {
        return (int) $user->type === 33;
    }

    public function delete(ApiUser $user, KategoriBahan $kategoriBahan): bool
    {
        return (int) $user->type === 33
            && !$kategoriBahan->bahan()->exists()
            && !$kategoriBahan->bahanByType()->exists()
            && !$kategoriBahan->lpbDetails()->exists();
    }
}
