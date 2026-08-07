<?php

namespace Database\Seeders;

use App\Models\Gudang;
use App\Models\Bahan;
use App\Models\ChartOfAccount;
use App\Models\InventoryLayer;
use App\Models\Jurnal;
use App\Models\KategoriBahan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Services\DocumentNumberService;

class WmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $category = KategoriBahan::where('katnama', 'Bahan Baku Paper')->firstOrFail();
            $warehouse = Gudang::where('nama', 'Gudang Utama')->firstOrFail();

            $material = Bahan::updateOrCreate(
                ['nama' => '[DEMO] Kertas Rekonsiliasi'],
                [
                    'kategori' => $category->id,
                    'keterangan_bahan' => 'Data simulasi aman untuk mencoba rekonsiliasi WMS',
                    'satuan' => 'KG',
                    'stok_onhand' => 25,
                    'stok_onpurchase' => 0,
                    'planning' => 0,
                    'stokawal' => 0,
                    'pengambilan_stokawal' => 0,
                    'tipe_gudang' => $warehouse->id,
                    'tipe_barang' => $category->id,
                ]
            );

            InventoryLayer::updateOrCreate(
                ['source_type' => 'DEMO_SEED', 'source_id' => $material->id],
                [
                    'bahan_id' => $material->id,
                    'gudang_id' => $warehouse->id,
                    'transaction_date' => now()->startOfMonth()->toDateString(),
                    'initial_quantity' => 25,
                    'remaining_quantity' => 25,
                    'unit_cost' => 10000,
                ]
            );

            $equity = ChartOfAccount::updateOrCreate(
                ['kode_akun' => '3101'],
                [
                    'nama_akun' => 'Ekuitas Data Demo',
                    'kategori_akun' => 'EKUITAS',
                    'posisi_normal' => 'KREDIT',
                    'keterangan' => 'Hanya digunakan WmsDemoSeeder',
                    'is_active' => true,
                    'is_postable' => true,
                    'is_cash_bank' => false,
                ]
            );

            $journal = Jurnal::updateOrCreate(
                ['sumber_transaksi' => 'DEMO_SEED', 'reff_id' => $material->id],
                [
                    'no_jurnal' => app(DocumentNumberService::class)->financial('JR', now()->startOfMonth()),
                    'tanggal' => now()->startOfMonth()->toDateString(),
                    'keterangan' => 'Jurnal seimbang untuk simulasi halaman rekonsiliasi',
                    'status' => 'POSTED',
                    'created_by' => 0,
                    'posted_by' => 0,
                    'posted_at' => now(),
                    'total_debit' => 250000,
                    'total_kredit' => 250000,
                ]
            );

            $journal->details()->delete();
            $journal->details()->createMany([
                [
                    'coa_id' => $category->coa_persediaan_id,
                    'debit' => 250000,
                    'kredit' => 0,
                    'keterangan' => 'Persediaan data demo',
                ],
                [
                    'coa_id' => $equity->id,
                    'debit' => 0,
                    'kredit' => 250000,
                    'keterangan' => 'Lawan persediaan data demo',
                ],
            ]);
        });

        $this->command?->info('Data demo rekonsiliasi dibuat: stok 25 KG, layer 25 KG, nilai Rp250.000, jurnal seimbang.');
    }
}
