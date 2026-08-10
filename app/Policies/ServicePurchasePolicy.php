<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ServicePurchase;

class ServicePurchasePolicy
{
    private function canViewServicePurchases(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewServicePurchases($user);
    }

    public function view(User $user, ServicePurchase $po): bool
    {
        return $this->canViewServicePurchases($user) && $po->document_type === 'SERVICE';
    }

    public function create(User $user): bool
    {
        return $this->canViewServicePurchases($user);
    }

    public function viewFinancials(User $user): bool
    {
        return $this->canViewServicePurchases($user);
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
