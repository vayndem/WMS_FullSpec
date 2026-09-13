<?php

namespace App\Policies;

use App\Models\LampiranDokumen;
use App\Models\User;

class LampiranDokumenPolicy
{
    public function view(User $user, LampiranDokumen $lampiran): bool
    {
        $induk = $lampiran->lampiran;

        return $induk !== null && $user->can('view', $induk);
    }

    public function delete(User $user, LampiranDokumen $lampiran): bool
    {
        return $this->view($user, $lampiran) && $user->id === $lampiran->user_id;
    }
}
