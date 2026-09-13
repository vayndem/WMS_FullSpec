<?php

namespace App\Console\Commands;

use App\Services\AbcAnalysisService;
use Illuminate\Console\Command;

class HitungKlasifikasiAbc extends Command
{
    protected $signature = 'wms:hitung-abc {--gudang= : Batasi ke satu gudang}';
    protected $description = 'Klasifikasikan ulang bahan ke kelas A/B/C berdasarkan nilai pemakaian 12 bulan terakhir';

    public function __construct(private AbcAnalysisService $abc)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $gudang = $this->option('gudang');
        $jumlah = $this->abc->hitung($gudang ? (int) $gudang : null);

        $this->info("{$jumlah} baris pengaturan bahan per gudang diklasifikasikan ulang.");

        return self::SUCCESS;
    }
}
