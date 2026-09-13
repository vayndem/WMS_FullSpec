<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PersetujuanMenunggu extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $konteks,
        private string $judul,
        private string $ringkasan,
        private string $url,
        private ?string $kodeDokumen = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'jenis' => 'PERSETUJUAN',
            'konteks' => $this->konteks,
            'judul' => $this->judul,
            'ringkasan' => $this->ringkasan,
            'kode_dokumen' => $this->kodeDokumen,
            'url' => $this->url,
            'ikon' => 'fa-circle-check',
            'warna' => 'warning',
        ];
    }
}
