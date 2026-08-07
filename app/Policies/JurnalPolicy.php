<?php

namespace App\Policies;

use App\Models\Jurnal;
use App\Models\ApiUser;

class JurnalPolicy
{
    public function viewAny(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function view(ApiUser $user, Jurnal $jurnal): bool
    {
        return (int) $user->type === 33;
    }

    public function create(ApiUser $user): bool
    {
        return (int) $user->type === 33;
    }

    public function update(ApiUser $user, Jurnal $jurnal): bool
    {
        return (int) $user->type === 33 && $jurnal->isManual() && $jurnal->isDraft();
    }

    public function delete(ApiUser $user, Jurnal $jurnal): bool
    {
        return (int) $user->type === 33 && $jurnal->isManual() && $jurnal->isDraft();
    }

    public function post(ApiUser $user, Jurnal $jurnal): bool
    {
        return (int) $user->type === 33 && $jurnal->isManual() && $jurnal->isDraft();
    }

    public function reverse(ApiUser $user, Jurnal $jurnal): bool
    {
        return (int) $user->type === 33 && $jurnal->isManual() && $jurnal->status === 'POSTED';
    }
}
