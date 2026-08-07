<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\InvoiceLpbImport;
use Exception;
use Illuminate\Support\Facades\Log;

class ImportLegacyInvoices extends Command
{
    protected $signature = 'import:legacy-invoices {--file=}';

    protected $description = 'Import legacy invoices dan LPB dari file Excel/CSV';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $filePath = $this->option('file');

        if (!$filePath) {
            $filePath = $this->ask('Masukkan path lengkap ke file Excel/CSV Anda (e.g., storage/app/Maret.xlsx - Sheet1.csv)');
        }

        $realPath = realpath($filePath);

        if (!$realPath || !file_exists($realPath)) {
            $realPath = realpath(base_path($filePath));
        }

        if (!$realPath || !file_exists($realPath)) {
            $this->error('File tidak ditemukan di path: ' . $filePath);
            $this->info('Pastikan path sudah benar. Contoh: storage/app/Maret.xlsx - Sheet1.csv');
            return 1;
        }

        $this->info("Memulai proses impor dari file: {$realPath}");
        $this->info('PASTIKAN ANDA SUDAH MEM-BACKUP DATABASE!');

        if (!$this->confirm('Apakah Anda ingin melanjutkan?', true)) {
            $this->info('Proses impor dibatalkan.');
            return 0;
        }

        try {
            Excel::import(new InvoiceLpbImport, $realPath);

            $this->info('---------------------------------');
            $this->info('✅ Proses impor selesai.');
            $this->info('Silakan cek log file Anda (storage/logs/laravel.log) untuk melihat baris yang mungkin dilewati (ditandai dengan [IMPORT_LEGACY]).');
        } catch (Exception $e) {
            $this->error('⛔️ Terjadi kesalahan besar saat impor: ' . $e->getMessage());
            Log::error('IMPORT_LEGACY: FATAL ERROR', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return 1;
        }
        return 0;
    }
}
