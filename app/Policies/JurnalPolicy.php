<?php

namespace App\Policies;

use App\Models\Jurnal;
use App\Models\User;

class JurnalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAccounting();
    }

    public function view(User $user, Jurnal $jurnal): bool
    {
        return $user->isAccounting();
    }

    public function create(User $user): bool
    {
        return $user->isAccounting();
    }

    public function update(User $user, Jurnal $jurnal): bool
    {
        return $user->isAccounting() && $jurnal->isManual() && $jurnal->isDraft();
    }

    public function delete(User $user, Jurnal $jurnal): bool
    {
        return $user->isAccounting() && $jurnal->isManual() && $jurnal->isDraft();
    }

    public function post(User $user, Jurnal $jurnal): bool
    {
        return $user->isAccounting() && $jurnal->isManual() && $jurnal->isDraft();
    }

    public function reverse(User $user, Jurnal $jurnal): bool
    {
        return $user->isAccounting() && $jurnal->isManual() && $jurnal->status === 'POSTED';
    }
}
