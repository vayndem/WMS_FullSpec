<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvarianMenyimpang extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private array $menyimpang) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $item = collect($this->menyimpang)->values();
        $barisBermasalah = $item->sum('invalid');

        return [
            'jenis' => 'INVARIAN',
            'konteks' => 'Rekonsiliasi akuntansi',
            'judul' => $item->count() . ' invarian menyimpang',
            'ringkasan' => $barisBermasalah . ' baris tidak cocok pada ' . $item->pluck('key')->implode(', '),
            'url' => route('reconciliation.index'),
            'ikon' => 'fa-scale-unbalanced',
            'warna' => 'error',
            'rincian' => $item->map(fn ($baris) => [
                'label' => $baris['label'],
                'catatan' => $baris['invalid'] . ' dari ' . $baris['total'] . ' baris menyimpang',
                'url' => route('reconciliation.index'),
            ])->all(),
        ];
    }
}
