<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PenerimaanBarangDetail;

class PenerimaanBarangDetailPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPurchasing();
    }

    public function view(User $user, PenerimaanBarangDetail $lpbdetail): bool
    {
        return $user->isPurchasing();
    }

    public function create(User $user): bool
    {
        return $user->isPurchasing();
    }

    public function update(User $user, PenerimaanBarangDetail $lpbdetail): bool
    {
        return $user->isPurchasing() && (int) ($lpbdetail->lpb->kunci ?? 0) === 0;
    }

    public function delete(User $user, PenerimaanBarangDetail $lpbdetail): bool
    {
        return $user->isPurchasing() && (int) ($lpbdetail->lpb->kunci ?? 0) === 0;
    }
}
