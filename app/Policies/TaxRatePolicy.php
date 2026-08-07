<?php

namespace App\Policies;

use App\Models\ApiUser;
use App\Models\TaxRate;

class TaxRatePolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }
    public function view(ApiUser $user, TaxRate $taxRate): bool
    {
        return (int) $user->type === 33;
    }
    public function create(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }
    public function update(ApiUser $user, TaxRate $taxRate): bool
    {
        return (int) $user->type === 33;
    }
    public function delete(ApiUser $user, TaxRate $taxRate): bool
    {
        return false;
    }
}
