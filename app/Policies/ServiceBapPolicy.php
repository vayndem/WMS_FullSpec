<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ServiceBap;

class ServiceBapPolicy
{
    private function canViewServiceBaps(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_WAREHOUSE, User::ROLE_ACCOUNTING]);
    }

    private function canViewServiceBapFinancials(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_PURCHASING, User::ROLE_ACCOUNTING]);
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewServiceBaps($user);
    }

    public function view(User $user, ServiceBap $bap): bool
    {
        return $this->canViewServiceBaps($user) && $bap->document_type === 'SERVICE_BAP';
    }

    public function create(User $user): bool
    {
        return $this->canViewServiceBaps($user);
    }

    public function cancel(User $user, ServiceBap $bap): bool
    {
        return $this->canViewServiceBapFinancials($user)
            && $this->view($user, $bap)
            && !$bap->is_cancelled
            && !$bap->invoiceReceipts()->exists();
    }

    public function viewFinancials(User $user): bool
    {
        return $this->canViewServiceBapFinancials($user);
    }
}
