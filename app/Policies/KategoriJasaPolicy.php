<?php

namespace App\Policies;

use App\Models\User;
use App\Models\KategoriJasa;

class KategoriJasaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }
    public function update(User $user, KategoriJasa $category): bool
    {
        return $user->isAccounting();
    }
}
