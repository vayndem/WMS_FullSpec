<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class PengingatTenggat extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private array $pengingat) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $item = collect($this->pengingat)->sortBy('hari')->values();
        $terlewat = $item->where('hari', '<', 0)->count();

        return [
            'jenis' => 'TENGGAT',
            'konteks' => $item->first()['konteks'] ?? 'Pengingat',
            'judul' => $item->count() . ' item perlu perhatian',
            'ringkasan' => $terlewat > 0
                ? $terlewat . ' di antaranya sudah melewati tenggat'
                : 'Tenggat terdekat: ' . $this->ringkasTanggal($item->first()),
            'url' => $item->first()['url'] ?? route('dashboard'),
            'ikon' => $terlewat > 0 ? 'fa-triangle-exclamation' : 'fa-clock',
            'warna' => $terlewat > 0 ? 'error' : 'info',
            'rincian' => $item->take(10)->map(fn ($row) => [
                'konteks' => $row['konteks'],
                'label' => $row['label'],
                'hari' => $row['hari'],
                'tanggal' => Carbon::parse($row['tanggal'])->toDateString(),
                'url' => $row['url'],
            ])->all(),
        ];
    }

    private function ringkasTanggal(?array $row): string
    {
        if (!$row) {
            return '-';
        }

        return Carbon::parse($row['tanggal'])->translatedFormat('d M Y');
    }
}
