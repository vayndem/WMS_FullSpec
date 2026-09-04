<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PembayaranFaktur;

class PembayaranFakturPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_FINANCE]);
    }

    public function view(User $user, PembayaranFaktur $payment): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_FINANCE]);
    }

    public function create(User $user): bool
    {
        return $user->isFinance();
    }

    public function update(User $user, PembayaranFaktur $payment): bool
    {
        return $user->isFinance();
    }

    public function delete(User $user, PembayaranFaktur $payment): bool
    {
        return $user->isFinance();
    }
}
