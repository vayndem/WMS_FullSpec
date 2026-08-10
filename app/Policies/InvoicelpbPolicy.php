<?php

namespace App\Policies;

use App\Models\Invoicelpb;
use App\Models\User;

class InvoicelpbPolicy
{
    private function canViewInvoices(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_FINANCE, User::ROLE_ACCOUNTING]);
    }

    private function canManageInvoices(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }

    private function canPayInvoices(User $user): bool
    {
        return $user->isFinance();
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewInvoices($user);
    }

    public function view(User $user, Invoicelpb $invoice): bool
    {
        return $this->canViewInvoices($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageInvoices($user);
    }

    public function update(User $user, Invoicelpb $invoice): bool
    {
        return $this->canManageInvoices($user) && !$invoice->is_void && !$invoice->details()->exists();
    }

    public function delete(User $user, Invoicelpb $invoice): bool
    {
        return $this->canManageInvoices($user) && !$invoice->is_void && !$invoice->details()->exists();
    }

    public function pay(User $user, Invoicelpb $invoice): bool
    {
        return $this->canPayInvoices($user) && !$invoice->is_void && (float) $invoice->sisa_tagihan > 0;
    }

    public function voidPayment(User $user, Invoicelpb $invoice): bool
    {
        return $this->canPayInvoices($user) && !$invoice->is_void;
    }
}
