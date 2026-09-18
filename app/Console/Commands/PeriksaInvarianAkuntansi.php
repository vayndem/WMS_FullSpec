<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AccountingReconciliationService;
use App\Services\NotifikasiService;
use Illuminate\Console\Command;

class PeriksaInvarianAkuntansi extends Command
{
    protected $signature = 'wms:periksa-invarian';
    protected $description = 'Periksa seluruh invarian rekonsiliasi dan beri tahu akuntansi bila ada yang menyimpang';

    public function __construct(
        private AccountingReconciliationService $rekonsiliasi,
        private NotifikasiService $notifikasi,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $invarian = $this->rekonsiliasi->checks();

        foreach ($invarian as $baris) {
            $this->line(sprintf(
                '%-10s %-52s %s',
                $baris['key'],
                $baris['label'],
                $baris['invalid'] > 0 ? "MENYIMPANG ({$baris['invalid']}/{$baris['total']})" : 'valid'
            ));
        }

        $menyimpang = $invarian->where('invalid', '>', 0)->values()->all();

        if ($menyimpang === []) {
            $this->info("Seluruh {$invarian->count()} invarian valid.");

            return self::SUCCESS;
        }

        $penerima = $this->notifikasi->kirimInvarianMenyimpang(
            [User::ROLE_ACCOUNTING, User::ROLE_ACCOUNTING_MANAGER],
            $menyimpang
        );

        $this->error(sprintf(
            '%d invarian menyimpang, %d notifikasi diantrikan.',
            count($menyimpang),
            $penerima
        ));

        return self::FAILURE;
    }
}
