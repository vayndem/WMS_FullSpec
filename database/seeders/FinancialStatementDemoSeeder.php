<?php

namespace Database\Seeders;

use App\Models\Bahan;
use App\Models\ChartOfAccount;
use App\Models\Gudang;
use App\Models\InventoryLayer;
use App\Models\InvoiceLpb;
use App\Models\InvoicePayment;
use App\Models\Jurnal;
use App\Models\KategoriBahan;
use App\Models\PenerimaanBarang;
use App\Models\PenerimaanBarangDetail;
use App\Models\Npk;
use App\Models\PesananPembelian;
use App\Models\PesananPembelianDetail;
use App\Models\StockOpname;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\PaymentAllocationService;
use App\Services\StockOpnameService;
use App\Services\StokGudangService;
use App\Services\WmsAccountingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FinancialStatementDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Bahan::where('nama', '[DEMO] Kertas Laporan Keuangan')->exists()) {
            $this->command?->warn('Skenario demo laporan keuangan sudah tersedia; tidak dibuat ulang.');
            $this->assertBalances();
            return;
        }

        $purchasingUser = User::where('email', 'purchasing@wms.local')->firstOrFail();
        $warehouseUser = User::where('email', 'warehouse@wms.local')->firstOrFail();
        $accountingUser = User::where('email', 'accounting@wms.local')->firstOrFail();
        Auth::setUser($purchasingUser);

        $accounting = app(WmsAccountingService::class);
        $stokGudang = app(StokGudangService::class);
        $opnameService = app(StockOpnameService::class);
        $allocation = app(PaymentAllocationService::class);
        $numbers = app(DocumentNumberService::class);

        DB::transaction(function () use (
            $accounting,
            $stokGudang,
            $opnameService,
            $allocation,
            $numbers,
            $purchasingUser,
            $warehouseUser,
            $accountingUser
        ) {
            $category = KategoriBahan::where('katnama', 'Bahan Baku Paper')->firstOrFail();
            $warehouse = Gudang::where('nama', 'Gudang Utama')->firstOrFail();
            $supplier = Supplier::where('nama', 'PT. Global Supply Indonesia')->firstOrFail();
            $bank = ChartOfAccount::where('kode_akun', '1102')->firstOrFail();
            $pendapatanSelisih = ChartOfAccount::where('kode_akun', '4201')->firstOrFail();

            $material = Bahan::create([
                'nama' => '[DEMO] Kertas Laporan Keuangan',
                'kategori' => $category->id,
                'keterangan_bahan' => 'Skenario multi-bulan untuk demo Neraca Saldo, Buku Besar, Laba Rugi, dan Neraca',
                'satuan' => 'RIM',
                'stok_onhand' => 0,
                'stok_onpurchase' => 0,
                'planning' => 10,
                'stokawal' => 0,
                'pengambilan_stokawal' => 0,
                'tipe_gudang' => $warehouse->id,
                'tipe_barang' => $category->id,
            ]);

            $date1 = today()->subDays(75);
            [, $lpb1] = $this->purchaseAndReceive($accounting, $stokGudang, $numbers, $material, $category, $warehouse, $supplier, $date1, 25, 25000, 'DEMO-FS-LOT-A');
            $invoice1 = $this->invoiceAndPay($accounting, $allocation, $numbers, $bank, $lpb1, $date1->copy()->addDays(5), $date1->copy()->addDays(20), 625000);
            $this->consumeMaterial($accounting, $numbers, $material, $warehouse, $date1->copy()->addDays(10), 5, 'DEMO-FS-JOB-A');

            $date2 = today()->subDays(45);
            [, $lpb2] = $this->purchaseAndReceive($accounting, $stokGudang, $numbers, $material, $category, $warehouse, $supplier, $date2, 20, 26000, 'DEMO-FS-LOT-B');
            $invoice2 = $this->invoiceAndPay($accounting, $allocation, $numbers, $bank, $lpb2, $date2->copy()->addDays(4), $date2->copy()->addDays(18), 520000, $pendapatanSelisih->id, 500, 'PENDAPATAN_SELISIH');
            $this->consumeMaterial($accounting, $numbers, $material, $warehouse, $date2->copy()->addDays(9), 8, 'DEMO-FS-JOB-B');

            $date3 = today()->subDays(15);
            $this->positiveOpname($opnameService, $numbers, $material, $warehouse, $warehouseUser, $accountingUser, $date3, 3, 25500);

            unset($invoice1, $invoice2);
            Auth::setUser($purchasingUser);
        });

        $this->assertBalances();
        $this->command?->info('Skenario demo laporan keuangan (multi-bulan, lintas kategori akun) berhasil dibuat.');
    }

    private function purchaseAndReceive(
        WmsAccountingService $accounting,
        StokGudangService $stokGudang,
        DocumentNumberService $numbers,
        Bahan $material,
        KategoriBahan $category,
        Gudang $warehouse,
        Supplier $supplier,
        Carbon $date,
        float $quantity,
        float $price,
        string $lot
    ): array {
        $po = PesananPembelian::create([
            'no_po' => $numbers->financial('PO', $date),
            'tanggal' => $date,
            'supplier_id' => $supplier->id,
            'gudang_id' => $warehouse->id,
            'untuk_perhatian' => 'Demo Purchasing',
            'term' => '30 hari',
            'notes' => "PO demo laporan keuangan {$lot}",
            'ppn' => 0,
            'total_exclude' => $quantity * $price,
            'total_ppn' => 0,
            'total_include' => $quantity * $price,
            'grand_total' => $quantity * $price,
            'status' => PesananPembelian::OPEN,
            'term_pengiriman' => 'Sekaligus',
            'jenis' => 0,
            'kunci' => 1,
        ]);
        PesananPembelianDetail::create([
            'no_po' => $po->no_po,
            'bahan_id' => $material->id,
            'jumlah' => $quantity,
            'harga' => $price,
            'exclude' => $quantity * $price,
            'ppn' => 0,
            'include' => $quantity * $price,
            'diterima' => $quantity,
            'jenis' => 0,
        ]);

        $lpbDate = $date->copy()->addDays(2);
        $lpbNumber = $numbers->external('LPB', $lpbDate);
        $lpb = PenerimaanBarang::create([
            'id_lpb' => $lpbNumber,
            'tanggal' => $lpbDate,
            'no_po' => $po->no_po,
            'gudang_id' => $warehouse->id,
            'no_sj' => "SJ-{$lpbNumber}",
            'id_user' => 5,
            'flag' => 0,
            'status' => PenerimaanBarang::POSTED,
            'jenis_lpb' => 1,
            'kunci' => 1,
        ]);
        $detail = PenerimaanBarangDetail::create([
            'id_lpb' => $lpb->id_lpb,
            'id_bahan' => $material->id,
            'id_kategori' => $category->id,
            'jumlah_barang_diterima' => $quantity,
            'lot_number' => $lot,
            'harga' => $price,
            'nilai_awal' => $quantity * $price,
            'jumlah_dipakai' => 0,
            'jumlah_tersisa' => $quantity,
            'flag_dipakai' => 1,
        ]);
        InventoryLayer::create([
            'bahan_id' => $material->id,
            'gudang_id' => $warehouse->id,
            'source_type' => 'LPB_DETAIL',
            'source_id' => $detail->id,
            'transaction_date' => $lpbDate,
            'initial_quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'unit_cost' => $price,
        ]);
        $accounting->postLpb($lpb);
        $stokGudang->masuk($warehouse->id, $material->id, $quantity, $price, 'LPB_MASUK', 'LPB', $lpb->id, "Penerimaan {$lpb->id_lpb}");

        return [$po, $lpb];
    }

    private function invoiceAndPay(
        WmsAccountingService $accounting,
        PaymentAllocationService $allocation,
        DocumentNumberService $numbers,
        ChartOfAccount $bank,
        PenerimaanBarang $lpb,
        Carbon $invoiceDate,
        Carbon $paymentDate,
        float $grandTotal,
        ?int $coaSelisihId = null,
        float $selisih = 0,
        ?string $jenisSelisih = null
    ): InvoiceLpb {
        $invoice = InvoiceLpb::create([
            'no_invoice' => $numbers->external('INV', $invoiceDate),
            'kode_supplier' => $lpb->pembelian->supplier_id,
            'tanggal' => $invoiceDate,
            'tgl_deadline_pembayaran' => $invoiceDate->copy()->addDays(30),
            'sub_total' => $grandTotal,
            'diskon' => 0,
            'ongkir' => 0,
            'ppn' => 0,
            'pph' => 0,
            'grand_total' => $grandTotal,
            'total_pembayaran' => 0,
            'sisa_tagihan' => $grandTotal,
            'note' => 'Invoice demo laporan keuangan',
            'status' => InvoiceLpb::UNPAID,
        ]);
        $invoice->receipts()->create(['lpb_id' => $lpb->id, 'amount' => $grandTotal]);
        $lpb->update(['no_invoice' => $invoice->no_invoice]);
        $accounting->postInvoice($invoice->fresh(['receipts.lpb.details.kategori']));

        $cash = $grandTotal - $selisih;
        $calc = $allocation->calculate($invoice->sisa_tagihan, $cash, 0, $selisih, $jenisSelisih);

        $payment = InvoicePayment::create([
            'payment_number' => $numbers->financial('PY', $paymentDate),
            'invoice_lpb_id' => $invoice->id,
            'tanggal_pembayaran' => $paymentDate,
            'metode_pembayaran' => 'Transfer Bank BCA',
            'coa_kas_bank_id' => $bank->id,
            'jumlah_pembayaran' => $cash,
            'potongan_pph23' => 0,
            'potongan_materai' => 0,
            'biaya_transfer_bank' => 0,
            'selisih_bayar' => $selisih,
            'jenis_selisih' => $jenisSelisih,
            'coa_selisih_id' => $coaSelisihId,
            'kelebihan_pembayaran' => 0,
            'uang_muka_dipakai' => 0,
            'total_transaksi_pengurang_hutang' => $calc['ap_reduction'],
            'keterangan' => 'Pembayaran demo laporan keuangan',
            'finance_user_id' => 13,
        ]);

        $totalPembayaran = $invoice->payments()->sum('total_transaksi_pengurang_hutang');
        $invoice->update([
            'total_pembayaran' => $totalPembayaran,
            'sisa_tagihan' => max(0, $invoice->grand_total - $totalPembayaran),
            'status' => InvoiceLpb::paymentStatus((float) $invoice->grand_total, (float) $totalPembayaran),
            'pph' => $invoice->payments()->sum('potongan_pph23'),
        ]);
        $accounting->postPayment($payment);

        return $invoice->fresh();
    }

    private function consumeMaterial(
        WmsAccountingService $accounting,
        DocumentNumberService $numbers,
        Bahan $material,
        Gudang $warehouse,
        Carbon $date,
        float $quantity,
        string $job
    ): Npk {
        $npk = Npk::create([
            'kode' => $numbers->external('NPK', $date),
            'kode_datapesanan' => $job,
            'tanggal' => $date,
            'id_barang' => $material->id,
            'id_gudang_asal' => $warehouse->id,
            'jumlah' => $quantity,
            'jumlah_terkirim' => $quantity,
            'tgl_terkirim' => $date,
            'status' => Npk::POSTED,
            'keterangan' => "Pemakaian demo laporan keuangan {$job}",
            'id_user' => 5,
            'operator' => 'Demo Gudang',
        ]);
        $accounting->consumeStock($npk);
        $material->decrement('stok_onhand', $quantity);
        $accounting->postNpk($npk->fresh());

        return $npk;
    }

    private function positiveOpname(
        StockOpnameService $opnameService,
        DocumentNumberService $numbers,
        Bahan $material,
        Gudang $warehouse,
        User $warehouseUser,
        User $accountingUser,
        Carbon $date,
        float $surplus,
        float $unitCost
    ): void {
        $available = (float) DB::table('stok_gudangs')
            ->where('gudang_id', $warehouse->id)
            ->where('bahan_id', $material->id)
            ->value('stok_tersedia');

        $opname = StockOpname::create([
            'number' => $numbers->internal('OPN', 'FS', $date),
            'warehouse_id' => $warehouse->id,
            'cutoff_at' => $date,
            'status' => StockOpname::SUBMITTED,
            'notes' => 'Demo koreksi positif untuk Laba Rugi dan Neraca',
            'created_by' => $warehouseUser->id,
            'submitted_by' => $warehouseUser->id,
            'submitted_at' => $date,
        ]);
        $detail = $opname->details()->create([
            'bahan_id' => $material->id,
            'system_quantity' => $available,
            'physical_quantity' => $available + $surplus,
            'difference_quantity' => $surplus,
            'reason' => 'Ditemukan kelebihan fisik saat cek ulang gudang',
        ]);

        Auth::setUser($warehouseUser);
        $opnameService->confirmPhysical($opname);

        Auth::setUser($accountingUser);
        $opnameService->confirmValuation($opname, [['id' => $detail->id, 'unit_cost' => $unitCost]]);
        $opname->update([
            'status' => StockOpname::APPROVED,
            'approved_by' => $accountingUser->id,
            'approved_at' => $date,
        ]);
        $opnameService->post($opname->fresh(['details.bahan.tipeBarang']));
    }

    private function assertBalances(): void
    {
        $unbalanced = Jurnal::query()->whereIn('status', ['POSTED', 'REVERSED'])
            ->whereRaw('ABS(total_debit - total_kredit) > 0.01')->exists();
        if ($unbalanced) {
            throw new RuntimeException('Seeder gagal: terdapat jurnal laporan keuangan demo yang tidak seimbang.');
        }
    }
}
