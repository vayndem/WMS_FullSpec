<?php

namespace App\Policies;

use App\Models\PemeriksaanConsider;
use App\Models\User;

class PemeriksaanConsiderPolicy
{
    private function ok(User $user): bool
    {
        return $user->isWarehouse();
    }

    public function viewAny(User $user): bool
    {
        return $this->ok($user);
    }

    public function view(User $user, PemeriksaanConsider $model): bool
    {
        return $this->ok($user);
    }

    public function create(User $user): bool
    {
        return $this->ok($user);
    }

    public function update(User $user, PemeriksaanConsider $model): bool
    {
        return $this->ok($user) && $model->status === PemeriksaanConsider::DRAFT;
    }

    public function delete(User $user, PemeriksaanConsider $model): bool
    {
        return $this->update($user, $model);
    }

    public function confirm(User $user, PemeriksaanConsider $model): bool
    {
        return $this->update($user, $model);
    }
}
