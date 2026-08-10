<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TaxRate;

class TaxRatePolicy
{
    private function canManageTaxRates(User $user): bool
    {
        return $user->isAccounting();
    }

    public function viewAny(User $user): bool
    {
        return $this->canManageTaxRates($user);
    }

    public function view(User $user, TaxRate $taxRate): bool
    {
        return $this->canManageTaxRates($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageTaxRates($user);
    }

    public function update(User $user, TaxRate $taxRate): bool
    {
        return $this->canManageTaxRates($user);
    }

    public function delete(User $user, TaxRate $taxRate): bool
    {
        return false;
    }
}
