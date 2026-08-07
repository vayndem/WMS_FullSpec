<?php

namespace App\Policies;

use App\Models\ApiUser;
use App\Models\ServicePurchase;

class ServicePurchasePolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return in_array((int)$user->type, [5, 33], true);
    }
    public function view(ApiUser $user, ServicePurchase $po): bool
    {
        return $this->viewAny($user) && $po->document_type === 'SERVICE';
    }
    public function create(ApiUser $user): bool
    {
        return $this->viewAny($user);
    }
    public function viewFinancials(ApiUser $user): bool
    {
        return in_array((int) $user->type, [5, 33], true);
    }
    public function update(ApiUser $user, ServicePurchase $po): bool
    {
        return $this->view($user, $po) && !$po->lpbs()->exists();
    }
    public function delete(ApiUser $user, ServicePurchase $po): bool
    {
        return $this->update($user, $po);
    }
}
