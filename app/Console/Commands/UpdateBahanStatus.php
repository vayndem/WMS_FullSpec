<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class UpdateBahanStatus extends Command
{
    protected $signature = 'bahan:update-status {file}';
    protected $description = 'Update jenis dan kategori bahan berdasarkan input file XLSX';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File tidak ditemukan di: $filePath");
            return;
        }

        $this->info("Memulai pemrosesan file Excel...");

        try {
            $data = Excel::toArray([], $filePath);
            $rows = $data[0];

            $rowCount = 0;
            foreach ($rows as $index => $row) {
                if ($index < 4) {
                    continue;
                }

                $namaBahan = isset($row[0]) ? trim($row[0]) : null;
                $statusGudang = isset($row[6]) ? trim($row[6]) : '';

                if (empty($namaBahan)) {
                    continue;
                }

                $exists = DB::table('bahan')->where('nama', $namaBahan)->exists();

                if ($exists) {
                    if (str_contains(strtoupper($statusGudang), 'BUKAN BARANG GUDANG')) {
                        $affectedRows = DB::table('bahan')
                            ->where('nama', $namaBahan)
                            ->update([
                                'jenis' => 2,
                                'kategori' => 17,
                                'updated_at' => now()
                            ]);

                        $logMsg = "DITEMUKAN & DIUBAH: Bahan '$namaBahan' ($affectedRows data) diupdate ke jenis 2 & kategori 17.";
                        $this->info($logMsg);
                        Log::info($logMsg);
                    } else {
                        $logMsg = "DITEMUKAN: Bahan '$namaBahan' tidak ada perubahan status.";
                        $this->line($logMsg);
                        Log::info($logMsg);
                    }
                } else {
                    $logMsg = "TIDAK DITEMUKAN: Nama '$namaBahan' tidak ada di database.";
                    $this->warn($logMsg);
                    Log::warning($logMsg);
                }

                $rowCount++;
            }

            $this->info("Proses selesai. Total $rowCount baris diproses.");
        } catch (\Exception $e) {
            $this->error("Terjadi kesalahan: " . $e->getMessage());
            Log::error("Excel Import Error: " . $e->getMessage());
        }
    }
}
