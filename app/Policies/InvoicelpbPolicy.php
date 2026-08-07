<?php

namespace App\Policies;

use App\Models\Invoicelpb;
use App\Models\ApiUser;

class InvoicelpbPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 13, 33], true);
    }

    public function view(ApiUser $user, Invoicelpb $invoice): bool
    {
        return in_array((int) $user->type, [5, 13, 33], true);
    }

    public function create(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 33], true);
    }

    public function update(ApiUser $user, Invoicelpb $invoice): bool
    {
        return in_array((int) $user->type, [5, 33], true) && !$invoice->is_void && !$invoice->details()->exists();
    }

    public function delete(ApiUser $user, Invoicelpb $invoice): bool
    {
        return in_array((int) $user->type, [5, 33], true) && !$invoice->is_void && !$invoice->details()->exists();
    }

    public function pay(ApiUser $user, Invoicelpb $invoice): bool
    {
        return (int) $user->type === 13 && !$invoice->is_void && (float) $invoice->sisa_tagihan > 0;
    }

    public function voidPayment(ApiUser $user, Invoicelpb $invoice): bool
    {
        return (int) $user->type === 13 && !$invoice->is_void;
    }
}
