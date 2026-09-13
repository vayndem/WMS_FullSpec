<?php

namespace App\Console\Commands;

use App\Services\ReplenishmentService;
use Illuminate\Console\Command;

class HitungSaranPengisianUlang extends Command
{
    protected $signature = 'wms:hitung-replenishment {--gudang= : Batasi ke satu gudang}';
    protected $description = 'Hitung ulang saran pengisian ulang berdasarkan reorder point dan safety stock';

    public function __construct(private ReplenishmentService $replenishment)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $gudang = $this->option('gudang');
        $jumlah = $this->replenishment->calculate($gudang ? (int) $gudang : null);

        $this->info("{$jumlah} saran pengisian ulang dihitung.");

        return self::SUCCESS;
    }
}
