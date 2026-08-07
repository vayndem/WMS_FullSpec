<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\ApiUser;

class SupplierPolicy
{
    public function viewAny(?ApiUser $user): bool
    {
        return true;
    }

    public function view(?ApiUser $user, Supplier $supplier): bool
    {
        return true;
    }

    public function create(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5]);
    }

    public function update(ApiUser $user, Supplier $supplier): bool
    {
        return in_array((int) $user->type, [5]);
    }

    public function delete(ApiUser $user, Supplier $supplier): bool
    {
        return in_array((int) $user->type, [5]);
    }

    public function restore(ApiUser $user, Supplier $supplier): bool
    {
        return in_array((int) $user->type, [5]);
    }

    public function forceDelete(ApiUser $user, Supplier $supplier): bool
    {
        return in_array((int) $user->type, [5]);
    }
}
