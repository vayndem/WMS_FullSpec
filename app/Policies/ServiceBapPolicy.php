<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ServiceBap;

class ServiceBapPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array((int)$user->type, [5, 14, 33], true);
    }
    public function view(User $user, ServiceBap $bap): bool
    {
        return $this->viewAny($user) && $bap->document_type === 'SERVICE_BAP';
    }
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }
    public function cancel(User $user, ServiceBap $bap): bool
    {
        return in_array((int)$user->type, [5, 33], true)
            && $this->view($user, $bap)
            && !$bap->is_cancelled
            && !$bap->invoiceReceipts()->exists();
    }

    public function viewFinancials(User $user): bool
    {
        return in_array((int)$user->type, [5, 33], true);
    }
}
