<?php

namespace App\Policies;

use App\Models\TransferGudang;
use App\Models\User;

class TransferGudangPolicy
{
    private function ok(User $u): bool
    {
        return (int)$u->type === 14;
    }
    public function viewAny(User $u): bool
    {
        return $this->ok($u);
    }
    public function view(User $u, TransferGudang $m): bool
    {
        return $this->ok($u);
    }
    public function create(User $u): bool
    {
        return $this->ok($u);
    }
    public function update(User $u, TransferGudang $m): bool
    {
        return $this->ok($u) && $m->status === TransferGudang::DRAFT;
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
        return $this->ok($u) && $m->status === TransferGudang::DIAJUKAN;
    }
}
