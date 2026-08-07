<?php

namespace Database\Seeders;

use App\Models\AccountingSetting;
use App\Models\AdminNamagudang;
use App\Models\ApiUser;
use App\Models\Bahan;
use App\Models\ChartOfAccount;
use App\Models\Invoicelpb;
use App\Models\Invoicelpbdetail;
use App\Models\InventoryLayer;
use App\Models\Jurnal;
use App\Models\KategoriBahan;
use App\Models\Lpb;
use App\Models\LpbDetail;
use App\Models\Npk;
use App\Models\Pembelian;
use App\Models\Pembeliandetail;
use App\Models\Request as RequestModel;
use App\Models\RequestDetail;
use App\Models\StockOpname;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Services\StockOpnameService;
use App\Services\WmsAccountingService;
use App\Services\DocumentNumberService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class WmsTransactionScenarioSeeder extends Seeder
{
    public function run(): void
    {
        Auth::setUser(new ApiUser([
            'id' => 5,
            'name' => 'WMS Demo Seeder',
            'type' => 5,
        ]));

        if (Pembelian::where('notes', 'PO demo diterima parsial 17 dari 20 KG')->exists()) {
            $this->command?->warn('Skenario transaksi DEMO sudah tersedia; tidak dibuat ulang.');
            $this->assertInvariants();
            return;
        }

        $accounting = app(WmsAccountingService::class);
        $opnameService = app(StockOpnameService::class);
        $numbers = app(DocumentNumberService::class);

        DB::transaction(function () use ($accounting, $opnameService, $numbers) {
            $category = KategoriBahan::where('katnama', 'Bahan Baku Paper')->firstOrFail();
            $warehouse = AdminNamagudang::where('nama', 'Gudang Utama')->firstOrFail();
            $supplier = Supplier::where('nama', 'PT. Global Supply Indonesia')->firstOrFail();
            $bank = ChartOfAccount::where('kode_akun', '1102')->firstOrFail();
            $date = today()->subDays(20);

            $material = Bahan::updateOrCreate(
                ['nama' => '[DEMO] Tinta Alur Transaksi'],
                [
                    'kategori' => $category->id,
                    'keterangan_bahan' => 'Request → PO → LPB → Invoice → Pembayaran → NPK',
                    'satuan' => 'KG',
                    'stok_onhand' => 0,
                    'stok_onpurchase' => 20,
                    'planning' => 15,
                    'stokawal' => 0,
                    'pengambilan_stokawal' => 0,
                    'tipe_gudang' => $warehouse->id,
                    'tipe_barang' => $category->id,
                ]
            );

            $approvedRequest = RequestModel::create([
                'no_request' => $numbers->internal('REQ', 'PO', $date),
                'status' => 'approved',
                'catatan_approver' => 'Skenario request yang sudah menjadi PO',
                'approved_by' => 33,
                'approved_at' => $date,
            ]);
            $approvedDetail = RequestDetail::create([
                'request_id' => $approvedRequest->id,
                'bahan_id' => $material->id,
                'nama_barang' => $material->nama,
                'jumlah_minta' => 20,
                'jumlah_acc' => 20,
                'realisasi' => 20,
                'keterangan' => 'Demo kebutuhan tinta',
                'kategori' => $category->id,
                'satuan' => 'KG',
                'tipe_gudang' => $warehouse->id,
                'tipe_barang' => $category->id,
            ]);

            $pendingRequest = RequestModel::create([
                'no_request' => $numbers->internal('REQ', 'PO', $date->copy()->addDay()),
                'status' => 'pending',
            ]);
            RequestDetail::create([
                'request_id' => $pendingRequest->id,
                'bahan_id' => $material->id,
                'nama_barang' => $material->nama,
                'jumlah_minta' => 6,
                'realisasi' => 0,
                'keterangan' => 'Demo request menunggu approval',
                'kategori' => $category->id,
                'satuan' => 'KG',
                'tipe_gudang' => $warehouse->id,
                'tipe_barang' => $category->id,
            ]);

            $unrealizedRequest = RequestModel::create([
                'no_request' => $numbers->internal('REQ', 'PO', $date->copy()->addDays(2)),
                'status' => 'approved',
                'approved_by' => 33,
                'approved_at' => today()->subDays(3),
            ]);
            RequestDetail::create([
                'request_id' => $unrealizedRequest->id,
                'bahan_id' => $material->id,
                'nama_barang' => $material->nama,
                'jumlah_minta' => 5,
                'jumlah_acc' => 5,
                'realisasi' => 0,
                'keterangan' => 'Demo approved belum direalisasikan',
                'kategori' => $category->id,
                'satuan' => 'KG',
                'tipe_gudang' => $warehouse->id,
                'tipe_barang' => $category->id,
            ]);

            $po = Pembelian::create([
                'no_po' => $numbers->financial('PO', $date),
                'tanggal' => $date,
                'supplier_id' => $supplier->id,
                'no_order' => $approvedRequest->no_request,
                'untuk_perhatian' => 'Demo Purchasing',
                'term' => '30 hari',
                'notes' => 'PO demo diterima parsial 17 dari 20 KG',
                'ppn' => 11,
                'total_exclude' => 200000,
                'total_ppn' => 22000,
                'total_include' => 222000,
                'grand_total' => 222000,
                'status' => 0,
                'term_pengiriman' => 'Bertahap',
                'jenis' => 0,
                'kunci' => 1,
            ]);
            $poDetail = Pembeliandetail::create([
                'no_po' => $po->no_po,
                'bahan_id' => $material->id,
                'jumlah' => 20,
                'harga' => 10000,
                'exclude' => 200000,
                'ppn' => 22000,
                'include' => 222000,
                'diterima' => 17,
                'request_detail_id' => $approvedDetail->id,
                'jenis' => 0,
            ]);

            $firstLpb = $this->createReceipt(
                $accounting,
                $material,
                $category,
                $warehouse,
                $po,
                $date->copy()->addDays(3),
                12,
                'DEMO-LOT-A'
            );
            $secondLpb = $this->createReceipt(
                $accounting,
                $material,
                $category,
                $warehouse,
                $po,
                $date->copy()->addDays(8),
                5,
                'DEMO-LOT-B'
            );
            $material->update(['stok_onhand' => 17, 'stok_onpurchase' => 3]);

            $npk = Npk::create([
                'kode' => $numbers->external('NPK', $date->copy()->addDays(10)),
                'kode_datapesanan' => 'DEMO-JOB-001',
                'tanggal' => $date->copy()->addDays(10),
                'id_barang' => $material->id,
                'id_gudang_asal' => $warehouse->id,
                'jumlah' => 4,
                'jumlah_terkirim' => 4,
                'tgl_terkirim' => $date->copy()->addDays(10),
                'close' => 1,
                'keterangan' => 'Pemakaian demo empat kilogram',
                'id_user' => 5,
                'operator' => 'Demo Gudang',
            ]);
            $accounting->consumeStock($npk);
            $material->decrement('stok_onhand', 4);
            $accounting->postNpk($npk->fresh());

            $subtotal = 120000;
            $ppnRate = TaxRate::rateFor('PPN', $date->copy()->addDays(12));
            $pphRate = TaxRate::rateFor('PPH23', $date->copy()->addDays(12));
            $ppn = round($subtotal * $ppnRate / 100, 2);
            $invoice = Invoicelpb::create([
                'no_invoice' => 'DEMO-INV-001',
                'kode_supplier' => $supplier->id,
                'tanggal' => $date->copy()->addDays(12),
                'tgl_deadline_pembayaran' => today()->subDays(2),
                'sub_total' => $subtotal,
                'jenis_pajak' => 'PPN',
                'dpp_ppn' => $subtotal,
                'tarif_ppn' => $ppnRate,
                'ppn' => $ppn,
                'dasar_pph' => $subtotal,
                'tarif_pph' => $pphRate,
                'diskon' => 0,
                'ongkir' => 0,
                'pph' => 0,
                'grand_total' => $subtotal + $ppn,
                'status_pembayaran' => 'Dibayar Sebagian',
                'total_pembayaran' => 50000,
                'sisa_tagihan' => $subtotal + $ppn - 50000,
                'note' => 'Invoice demo terlambat dan sudah dibayar sebagian',
                'status' => 1,
            ]);
            $invoice->receipts()->create(['lpb_id' => $firstLpb->id, 'amount' => $subtotal]);
            $firstLpb->update(['no_invoice' => $invoice->no_invoice]);
            $accounting->postInvoice($invoice);

            $payment = Invoicelpbdetail::create([
                'payment_number' => $numbers->financial('PY', today()->subDay()),
                'id_invoice_lpb' => $invoice->id,
                'tanggal_pembayaran' => today()->subDay(),
                'metode_pembayaran' => 'Transfer Bank BCA',
                'coa_kas_bank_id' => $bank->id,
                'jumlah_pembayaran' => 50000,
                'potongan_pph23' => 0,
                'potongan_materai' => 0,
                'biaya_transfer_bank' => 0,
                'selisih_bayar' => 0,
                'kelebihan_pembayaran' => 0,
                'total_transaksi_pengurang_hutang' => 50000,
                'keterangan' => 'Pembayaran parsial demo',
                'id_user_finance' => 13,
            ]);
            $accounting->postPayment($payment);

            $opname = StockOpname::create([
                'number' => $numbers->internal('OPN', 'INV', now()),
                'warehouse_id' => $warehouse->id,
                'cutoff_at' => now(),
                'status' => StockOpname::SUBMITTED,
                'notes' => 'Opname demo menunggu approval type 33',
                'created_by' => 14,
                'submitted_by' => 14,
                'submitted_at' => now(),
            ]);
            $opname->details()->create([
                'bahan_id' => $material->id,
                'system_quantity' => 13,
                'physical_quantity' => 12,
                'difference_quantity' => -1,
                'reason' => 'Selisih hitung demo',
                'notes' => 'Gunakan untuk mencoba approve/reject',
            ]);
            Auth::setUser(new ApiUser([
                'id' => 14,
                'name' => 'Gudang Demo Seeder',
                'type' => 14,
            ]));
            $opnameService->confirmPhysical($opname);
            Auth::setUser(new ApiUser([
                'id' => 5,
                'name' => 'WMS Demo Seeder',
                'type' => 5,
            ]));

            // Keep variables explicitly used so future scenario additions cannot
            // accidentally remove the second GRNI receipt or PO linkage.
            unset($secondLpb, $poDetail);
        });

        $this->assertInvariants();
        $this->command?->info('Skenario WMS end-to-end berhasil dibuat dan seluruh invariant akuntansi valid.');
    }

    private function createReceipt(
        WmsAccountingService $accounting,
        Bahan $material,
        KategoriBahan $category,
        AdminNamagudang $warehouse,
        Pembelian $po,
        $date,
        float $quantity,
        string $lot
    ): Lpb {
        $number = app(DocumentNumberService::class)->external('LPB', $date);
        $lpb = Lpb::create([
            'id_lpb' => $number,
            'tanggal' => $date,
            'no_po' => $po->no_po,
            'no_sj' => 'SJ-' . $number,
            'id_user' => 5,
            'flag' => 0,
            'status' => 1,
            'jenis_lpb' => 1,
            'kunci' => 1,
        ]);
        $detail = LpbDetail::create([
            'id_lpb' => $lpb->id_lpb,
            'id_bahan' => $material->id,
            'id_kategori' => $category->id,
            'jumlah_barang_diterima' => $quantity,
            'lot_number' => $lot,
            'harga' => 10000,
            'nilai_awal' => $quantity * 10000,
            'jumlah_dipakai' => 0,
            'jumlah_tersisa' => $quantity,
            'flag_dipakai' => 1,
        ]);
        InventoryLayer::create([
            'bahan_id' => $material->id,
            'gudang_id' => $warehouse->id,
            'source_type' => 'LPB_DETAIL',
            'source_id' => $detail->id,
            'transaction_date' => $date,
            'initial_quantity' => $quantity,
            'remaining_quantity' => $quantity,
            'unit_cost' => 10000,
        ]);
        $accounting->postLpb($lpb);
        return $lpb;
    }

    private function assertInvariants(): void
    {
        $material = Bahan::where('nama', '[DEMO] Tinta Alur Transaksi')->firstOrFail();
        $layerQuantity = (float) InventoryLayer::where('bahan_id', $material->id)->sum('remaining_quantity');
        if (abs((float) $material->stok_onhand - $layerQuantity) > 0.01) {
            throw new RuntimeException("Seeder gagal: stok {$material->stok_onhand} tidak sama dengan layer {$layerQuantity}.");
        }

        $unbalanced = Jurnal::query()->whereIn('status', ['POSTED', 'REVERSED'])
            ->whereRaw('ABS(total_debit - total_kredit) > 0.01')->exists();
        if ($unbalanced) {
            throw new RuntimeException('Seeder gagal: terdapat jurnal yang tidak seimbang.');
        }

        $invoice = Invoicelpb::where('no_invoice', 'DEMO-INV-001')->firstOrFail();
        if (abs((float) $invoice->sisa_tagihan - ((float) $invoice->grand_total - (float) $invoice->total_pembayaran)) > 0.01) {
            throw new RuntimeException('Seeder gagal: sisa invoice tidak sesuai grand total dikurangi pembayaran.');
        }

        $grniIds = KategoriBahan::whereNotNull('coa_clearing_lpb_id')->pluck('coa_clearing_lpb_id');
        $grniLedger = (float) DB::table('jurnal_details')->join('jurnals', 'jurnals.id', '=', 'jurnal_details.jurnal_id')
            ->whereIn('jurnals.status', ['POSTED', 'REVERSED'])->whereIn('jurnal_details.coa_id', $grniIds)
            ->selectRaw('COALESCE(SUM(kredit-debit),0) balance')->value('balance');
        $grniExpected = (float) DB::table('lpbdetails')->join('lpbs', 'lpbs.id_lpb', '=', 'lpbdetails.id_lpb')
            ->leftJoin('invoice_lpb_receipts', 'invoice_lpb_receipts.lpb_id', '=', 'lpbs.id')
            ->whereNull('invoice_lpb_receipts.id')
            ->sum(DB::raw('lpbdetails.jumlah_barang_diterima * lpbdetails.harga'));
        if (abs($grniLedger - $grniExpected) > 0.01) {
            throw new RuntimeException("Seeder gagal: GRNI ledger {$grniLedger} tidak sama dengan LPB belum ditagih {$grniExpected}.");
        }

        $apId = AccountingSetting::accountId(AccountingSetting::HUTANG_USAHA);
        $apLedger = (float) DB::table('jurnal_details')->join('jurnals', 'jurnals.id', '=', 'jurnal_details.jurnal_id')
            ->whereIn('jurnals.status', ['POSTED', 'REVERSED'])->where('jurnal_details.coa_id', $apId)
            ->selectRaw('COALESCE(SUM(kredit-debit),0) balance')->value('balance');
        $invoiceOutstanding = (float) Invoicelpb::where('is_void', false)->sum('sisa_tagihan');
        if (abs($apLedger - $invoiceOutstanding) > 0.01) {
            throw new RuntimeException("Seeder gagal: hutang ledger {$apLedger} tidak sama dengan sisa invoice {$invoiceOutstanding}.");
        }
    }
}
