<?php

namespace App\Policies;

use App\Models\User;
use App\Models\InvoicePayment;

class InvoicePaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_FINANCE]);
    }

    public function view(User $user, InvoicePayment $payment): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_FINANCE]);
    }

    public function create(User $user): bool
    {
        return $user->isPurchasing();
    }

    public function update(User $user, InvoicePayment $payment): bool
    {
        return $user->isFinance();
    }

    public function delete(User $user, InvoicePayment $payment): bool
    {
        return $user->isFinance();
    }
}
