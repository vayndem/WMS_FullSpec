<?php

namespace App\Policies;

use App\Models\User;
use App\Models\invoicelpbdetail;
use Illuminate\Auth\Access\Response;

class InvoicelpbdetailPolicy
{

    public function viewAny(User $user): bool
    {
        return in_array((int) $user->type, [5, 13]);
    }

    public function view(User $user, invoicelpbdetail $invoice): bool
    {
        return in_array((int) $user->type, [5, 13]);
    }

    public function create(User $user): bool
    {
        return (int) $user->type === 5;
    }

    public function update(User $user, invoicelpbdetail $invoice): bool
    {
        return (int) $user->type === 13;
    }

    public function delete(User $user, invoicelpbdetail $invoice): bool
    {
        return (int) $user->type === 13;
    }
}
