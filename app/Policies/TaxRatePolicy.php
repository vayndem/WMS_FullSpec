<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TaxRate;

class TaxRatePolicy
{
    public function viewAny(User $user): bool
    {
        return (int) $user->type === 33;
    }
    public function view(User $user, TaxRate $taxRate): bool
    {
        return (int) $user->type === 33;
    }
    public function create(User $user): bool
    {
        return (int) $user->type === 33;
    }
    public function update(User $user, TaxRate $taxRate): bool
    {
        return (int) $user->type === 33;
    }
    public function delete(User $user, TaxRate $taxRate): bool
    {
        return false;
    }
}
