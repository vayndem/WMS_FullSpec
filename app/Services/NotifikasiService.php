<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\InvarianMenyimpang;
use App\Notifications\PengingatTenggat;
use App\Notifications\PersetujuanMenunggu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotifikasiService
{
    public function penerimaBerdasarkanPeran(array $roles): Collection
    {
        return User::aktif()->berperan($roles)->get();
    }

    public function mintaPersetujuan(array $roles, string $konteks, string $judul, string $ringkasan, string $url, ?string $kodeDokumen = null): int
    {
        $penerima = $this->penerimaBerdasarkanPeran($roles);

        if ($penerima->isEmpty()) {
            return 0;
        }

        Notification::send($penerima, new PersetujuanMenunggu($konteks, $judul, $ringkasan, $url, $kodeDokumen));

        return $penerima->count();
    }

    public function kirimPengingat(array $roles, Collection $pengingat): int
    {
        if ($pengingat->isEmpty()) {
            return 0;
        }

        $penerima = $this->penerimaBerdasarkanPeran($roles);

        if ($penerima->isEmpty()) {
            return 0;
        }

        Notification::send($penerima, new PengingatTenggat($pengingat->values()->all()));

        return $penerima->count();
    }

    public function kirimInvarianMenyimpang(array $roles, array $menyimpang): int
    {
        if ($menyimpang === []) {
            return 0;
        }

        $penerima = $this->penerimaBerdasarkanPeran($roles);

        if ($penerima->isEmpty()) {
            return 0;
        }

        Notification::send($penerima, new InvarianMenyimpang($menyimpang));

        return $penerima->count();
    }
}
