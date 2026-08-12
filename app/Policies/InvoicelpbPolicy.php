<?php

namespace App\Policies;

use App\Models\InvoiceLpb;
use App\Models\User;

class InvoiceLpbPolicy
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

    public function view(User $user, InvoiceLpb $invoice): bool
    {
        return $this->canViewInvoices($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageInvoices($user);
    }

    public function update(User $user, InvoiceLpb $invoice): bool
    {
        return $this->canManageInvoices($user)
            && $invoice->status !== InvoiceLpb::VOID
            && !$invoice->payments()->exists()
            && $invoice->match_status === 'PENDING'
            && !\App\Models\Jurnal::where('sumber_transaksi', 'INVOICE_SUPPLIER')->where('reff_id', $invoice->id)->exists();
    }

    public function delete(User $user, InvoiceLpb $invoice): bool
    {
        return $this->canManageInvoices($user) && $invoice->status !== InvoiceLpb::VOID && !$invoice->payments()->exists();
    }

    public function pay(User $user, InvoiceLpb $invoice): bool
    {
        return $this->canPayInvoices($user) && $invoice->status !== InvoiceLpb::VOID && (float) $invoice->sisa_tagihan > 0;
    }

    public function voidPayment(User $user, InvoiceLpb $invoice): bool
    {
        return $this->canPayInvoices($user) && $invoice->status !== InvoiceLpb::VOID;
    }
}
