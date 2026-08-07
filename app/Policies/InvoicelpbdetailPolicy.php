<?php

namespace App\Policies;

use App\Models\ApiUser;
use App\Models\invoicelpbdetail;
use Illuminate\Auth\Access\Response;

class InvoicelpbdetailPolicy
{

    public function viewAny(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 13]);
    }

    public function view(ApiUser $user, invoicelpbdetail $invoice): bool
    {
        return in_array((int) $user->type, [5, 13]);
    }

    public function create(ApiUser $user): bool
    {
        return (int) $user->type === 5;
    }

    public function update(ApiUser $user, invoicelpbdetail $invoice): bool
    {
        return (int) $user->type === 13;
    }

    public function delete(ApiUser $user, invoicelpbdetail $invoice): bool
    {
        return (int) $user->type === 13;
    }
}
