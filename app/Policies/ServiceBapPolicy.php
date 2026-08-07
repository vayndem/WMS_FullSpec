<?php

namespace App\Policies;

use App\Models\ApiUser;
use App\Models\ServiceBap;

class ServiceBapPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return in_array((int)$user->type, [5, 14, 33], true);
    }
    public function view(ApiUser $user, ServiceBap $bap): bool
    {
        return $this->viewAny($user) && $bap->document_type === 'SERVICE_BAP';
    }
    public function create(ApiUser $user): bool
    {
        return $this->viewAny($user);
    }
    public function cancel(ApiUser $user, ServiceBap $bap): bool
    {
        return in_array((int)$user->type, [5, 33], true)
            && $this->view($user, $bap)
            && !$bap->is_cancelled
            && !$bap->invoiceReceipts()->exists();
    }

    public function viewFinancials(ApiUser $user): bool
    {
        return in_array((int)$user->type, [5, 33], true);
    }
}
