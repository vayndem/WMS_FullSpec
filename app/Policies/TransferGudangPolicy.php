<?php

namespace App\Policies;

use App\Models\TransferGudang;
use App\Models\User;

class TransferGudangPolicy
{
    private function ok(User $u): bool
    {
        return $u->isWarehouseOperator();
    }

    private function canAccessTransfer(User $u, TransferGudang $m): bool
    {
        if (!$u->isProduction()) {
            return true;
        }

        return $u->canAccessGudang((int) $m->gudang_asal_id, 'transfer')
            || $u->canAccessGudang((int) $m->gudang_tujuan_id, 'transfer');
    }
    public function viewAny(User $u): bool
    {
        return $this->ok($u);
    }
    public function view(User $u, TransferGudang $m): bool
    {
        return $this->ok($u) && $this->canAccessTransfer($u, $m);
    }
    public function create(User $u): bool
    {
        return $this->ok($u);
    }
    public function update(User $u, TransferGudang $m): bool
    {
        return $this->ok($u) && $this->canAccessTransfer($u, $m) && $m->status === TransferGudang::DRAFT;
    }
    public function delete(User $u, TransferGudang $m): bool
    {
        return $this->update($u, $m);
    }
    public function submit(User $u, TransferGudang $m): bool
    {
        return $this->update($u, $m);
    }
    public function confirm(User $u, TransferGudang $m): bool
    {
        return $this->ok($u) && $this->canAccessTransfer($u, $m) && $m->status === TransferGudang::DIAJUKAN;
    }

    public function receive(User $u, TransferGudang $m): bool
    {
        return $this->ok($u) && $this->canAccessTransfer($u, $m) && $m->status === TransferGudang::DIKIRIM;
    }
}
