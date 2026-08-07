<?php

namespace App\Policies;

use App\Models\Invoicelpb;
use App\Models\User;

class InvoicelpbPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array((int) $user->type, [5, 13, 33], true);
    }

    public function view(User $user, Invoicelpb $invoice): bool
    {
        return in_array((int) $user->type, [5, 13, 33], true);
    }

    public function create(User $user): bool
    {
        return in_array((int) $user->type, [5, 33], true);
    }

    public function update(User $user, Invoicelpb $invoice): bool
    {
        return in_array((int) $user->type, [5, 33], true) && !$invoice->is_void && !$invoice->details()->exists();
    }

    public function delete(User $user, Invoicelpb $invoice): bool
    {
        return in_array((int) $user->type, [5, 33], true) && !$invoice->is_void && !$invoice->details()->exists();
    }

    public function pay(User $user, Invoicelpb $invoice): bool
    {
        return (int) $user->type === 13 && !$invoice->is_void && (float) $invoice->sisa_tagihan > 0;
    }

    public function voidPayment(User $user, Invoicelpb $invoice): bool
    {
        return (int) $user->type === 13 && !$invoice->is_void;
    }
}
