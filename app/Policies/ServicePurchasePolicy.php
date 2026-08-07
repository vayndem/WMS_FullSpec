<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ServicePurchase;

class ServicePurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array((int)$user->type, [5, 33], true);
    }
    public function view(User $user, ServicePurchase $po): bool
    {
        return $this->viewAny($user) && $po->document_type === 'SERVICE';
    }
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }
    public function viewFinancials(User $user): bool
    {
        return in_array((int) $user->type, [5, 33], true);
    }
    public function update(User $user, ServicePurchase $po): bool
    {
        return $this->view($user, $po) && !$po->lpbs()->exists();
    }
    public function delete(User $user, ServicePurchase $po): bool
    {
        return $this->update($user, $po);
    }
}
