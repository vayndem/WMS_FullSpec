<?php

namespace App\Services;

use App\Models\AccountingSetting;
use App\Models\Invoicelpb;
use App\Models\Invoicelpbdetail;
use App\Models\Jurnal;
use App\Models\Lpb;
use App\Models\LpbDetail;
use App\Models\Npk;
use App\Models\NpkStockAllocation;
use App\Models\InventoryLayer;
use App\Models\ChartOfAccount;
use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WmsAccountingService
{
    public function __construct(
        private AccountingPeriodService $periods,
        private InventoryCostCalculator $costCalculator,
        private DocumentNumberService $numbers
    ) {}

    public function postLpb(Lpb $lpb): Jurnal
    {
        $this->periods->assertOpen($lpb->tanggal, 'LPB');
        if ($lpb->document_type === 'SERVICE_BAP') {
            throw new RuntimeException('BAP jasa hanya menandai pekerjaan dimulai dan tidak membentuk jurnal.');
        }
        $lpb->loadMissing('details.kategori');
        $lines = [];

        foreach ($lpb->details->groupBy('id_kategori') as $details) {
            $category = $details->first()->kategori;
            $this->assertCategoryMapping($category);
            $amount = $details->sum(fn($detail) => (float) $detail->jumlah_barang_diterima * (float) $detail->harga);
            $this->line($lines, $category->coa_persediaan_id, $amount, 0, "Persediaan {$category->katnama}");
            $this->line($lines, $category->coa_clearing_lpb_id, 0, $amount, "GRNI {$category->katnama}");
        }

        return $this->post("LPB-{$lpb->id_lpb}", $lpb->tanggal, 'LPB', $lpb->id, "Penerimaan barang {$lpb->id_lpb}", $lines);
    }

    public function postNpk(Npk $npk): Jurnal
    {
        $this->periods->assertOpen($npk->tanggal, 'NPK');
        $npk->loadMissing('barang.tipeBarang');
        $category = $npk->barang?->tipeBarang;
        $this->assertCategoryMapping($category);

        $amount = (float) $npk->total_nilai;
        if ($amount <= 0) {
            throw new RuntimeException('Nilai pemakaian NPK harus lebih besar dari nol.');
        }

        return $this->post("NPK-{$npk->kode}-{$npk->id}", $npk->tanggal, 'NPK', $npk->id, "Pemakaian barang {$npk->kode}", [
            ['coa_id' => $category->coa_beban_id, 'debit' => $amount, 'kredit' => 0, 'keterangan' => "Pemakaian {$category->katnama}"],
            ['coa_id' => $category->coa_persediaan_id, 'debit' => 0, 'kredit' => $amount, 'keterangan' => "Pengurangan persediaan {$category->katnama}"],
        ]);
    }

    public function consumeStock(Npk $npk): void
    {
        $quantity = (float) ($npk->jumlah_stok ?: $npk->jumlah);
        $layers = InventoryLayer::query()
            ->where('bahan_id', $npk->id_barang)
            ->when($npk->id_gudang_asal, fn($query) => $query->where('gudang_id', $npk->id_gudang_asal))
            ->where('remaining_quantity', '>', 0)
            ->whereDate('transaction_date', '<=', $npk->tanggal)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($layers->sum('remaining_quantity') < $quantity) {
            throw new RuntimeException('Stok layer LPB tidak mencukupi untuk NPK ini.');
        }

        $remaining = $quantity;
        $totalCost = 0;

        foreach ($layers as $layer) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($remaining, (float) $layer->remaining_quantity);
            $unitCost = (float) $layer->unit_cost;
            $cost = round($take * $unitCost, 2);
            NpkStockAllocation::create([
                'npk_id' => $npk->id,
                'inventory_layer_id' => $layer->id,
                'quantity' => $take,
                'unit_cost' => $unitCost,
                'total_cost' => $cost,
            ]);
            $newRemaining = (float) $layer->remaining_quantity - $take;
            $layer->update(['remaining_quantity' => $newRemaining]);
            if ($layer->source_type === 'LPB_DETAIL') {
                LpbDetail::whereKey($layer->source_id)->update([
                    'jumlah_dipakai' => DB::raw('jumlah_dipakai + ' . (float) $take),
                    'jumlah_tersisa' => $newRemaining,
                    'flag_dipakai' => $newRemaining > 0 ? 1 : 0,
                ]);
            }
            $remaining -= $take;
            $totalCost += $cost;
        }

        $effectiveUnitCost = $quantity > 0 ? round($totalCost / $quantity, 4) : 0;
        $npk->update(['harga_satuan' => $effectiveUnitCost, 'total_nilai' => $totalCost, 'status_posting' => 'POSTED']);
    }

    public function restoreStock(Npk $npk): void
    {
        foreach ($npk->allocations()->lockForUpdate()->get() as $allocation) {
            $layer = InventoryLayer::lockForUpdate()->findOrFail($allocation->inventory_layer_id);
            $layer->update([
                'remaining_quantity' => (float) $layer->remaining_quantity + (float) $allocation->quantity,
            ]);
            if ($layer->source_type === 'LPB_DETAIL') {
                LpbDetail::whereKey($layer->source_id)->update([
                    'jumlah_dipakai' => DB::raw('GREATEST(jumlah_dipakai - ' . (float) $allocation->quantity . ', 0)'),
                    'jumlah_tersisa' => $layer->remaining_quantity,
                    'flag_dipakai' => 1,
                ]);
            }
        }
        $npk->allocations()->delete();
        $this->deleteAutomaticJournal('NPK', $npk->id);
    }

    public function postInvoice(Invoicelpb $invoice): Jurnal
    {
        $this->periods->assertOpen($invoice->tanggal, 'Invoice supplier');
        $invoice->loadMissing(['receipts.lpb.details.kategori', 'receipts.lpb.serviceDetails.servicePoDetail.category']);
        if ($invoice->receipts->isEmpty()) {
            throw new RuntimeException('Invoice harus memiliki minimal satu LPB.');
        }

        $lines = [];
        foreach ($invoice->receipts->flatMap(fn($receipt) => $receipt->lpb->details)->groupBy('id_kategori') as $details) {
            $category = $details->first()->kategori;
            $this->assertCategoryMapping($category);
            $amount = $details->sum(fn($detail) => (float) $detail->jumlah_barang_diterima * (float) $detail->harga);
            $this->line($lines, $category->coa_clearing_lpb_id, $amount, 0, "Penyelesaian GRNI {$category->katnama}");
        }
        foreach ($invoice->receipts->flatMap(fn($receipt) => $receipt->lpb->serviceDetails)->groupBy('servicePoDetail.service_category_id') as $details) {
            $category = $details->first()->servicePoDetail->category;
            if (!$category) {
                throw new RuntimeException('Kategori jasa pada BAP tidak tersedia.');
            }
            $expensePairs = $category->code === ServiceCategory::PRODUCTION
                ? [['ASET', 'DEBIT']]
                : [['BEBAN', 'DEBIT']];
            ChartOfAccount::assertUsable($category->expense_coa_id, $expensePairs, "beban/WIP jasa {$category->name}");
            ChartOfAccount::assertUsable($category->grni_coa_id, [['LIABILITAS', 'KREDIT']], "GRNI jasa {$category->name}");
            $this->line($lines, $category->expense_coa_id, $details->sum('amount'), 0, "Penyelesaian jasa {$category->name}");
        }

        if ((float) $invoice->ppn > 0) {
            $this->line($lines, AccountingSetting::accountId(AccountingSetting::PPN_MASUKAN), (float) $invoice->ppn, 0, 'PPN Masukan');
        }
        $this->line($lines, AccountingSetting::accountId(AccountingSetting::BIAYA_ONGKIR), (float) $invoice->ongkir, 0, 'Biaya angkut pembelian');
        $this->line($lines, AccountingSetting::accountId(AccountingSetting::DISKON_PEMBELIAN), 0, (float) $invoice->diskon, 'Diskon pembelian');
        $this->line($lines, AccountingSetting::accountId(AccountingSetting::HUTANG_USAHA), 0, (float) $invoice->grand_total, 'Hutang supplier');

        return $this->post("INV-{$invoice->no_invoice}", $invoice->tanggal, 'INVOICE_SUPPLIER', $invoice->id, "Invoice supplier {$invoice->no_invoice}", $lines);
    }

    public function postPayment(Invoicelpbdetail $payment): Jurnal
    {
        $this->periods->assertOpen($payment->tanggal_pembayaran, 'Pembayaran supplier');
        $payment->loadMissing('invoice');
        $invoice = $payment->invoice;
        ChartOfAccount::assertUsable($payment->coa_kas_bank_id, [['ASET', 'DEBIT']], 'kas/bank pembayaran', true);
        if ($payment->jenis_selisih) {
            $differencePairs = match ($payment->jenis_selisih) {
                'PENDAPATAN_SELISIH' => [['PENDAPATAN', 'KREDIT']],
                'BEBAN_SELISIH' => [['BEBAN', 'DEBIT']],
                'UANG_MUKA_SUPPLIER' => [['ASET', 'DEBIT']],
                default => throw new RuntimeException('Jenis selisih pembayaran tidak dikenali.'),
            };
            ChartOfAccount::assertUsable($payment->coa_selisih_id, $differencePairs, 'selisih pembayaran');
        }
        $apReduction = (float) $payment->total_transaksi_pengurang_hutang;
        $lines = [];
        $this->line($lines, AccountingSetting::accountId(AccountingSetting::HUTANG_USAHA), $apReduction, 0, "Pelunasan {$invoice->no_invoice}");
        $this->line($lines, $payment->coa_kas_bank_id, 0, (float) $payment->jumlah_pembayaran + (float) $payment->biaya_transfer_bank + (float) $payment->potongan_materai, 'Kas/bank keluar');
        $this->line($lines, AccountingSetting::accountId(AccountingSetting::HUTANG_PPH23), 0, (float) $payment->potongan_pph23, 'PPh 23 dipotong saat pembayaran');
        $this->line($lines, AccountingSetting::accountId(AccountingSetting::BEBAN_MATERAI), (float) $payment->potongan_materai, 0, 'Beban materai');
        if ($payment->jenis_selisih === 'PENDAPATAN_SELISIH') {
            $this->line($lines, $payment->coa_selisih_id, 0, (float) $payment->selisih_bayar, 'Pendapatan selisih pembayaran');
        } elseif ($payment->jenis_selisih === 'BEBAN_SELISIH') {
            $this->line($lines, $payment->coa_selisih_id, (float) $payment->selisih_bayar, 0, 'Beban selisih pembayaran');
        } elseif ($payment->jenis_selisih === 'UANG_MUKA_SUPPLIER') {
            $this->line($lines, $payment->coa_selisih_id, (float) $payment->kelebihan_pembayaran, 0, 'Uang muka supplier');
        }
        $this->line($lines, AccountingSetting::accountId(AccountingSetting::BIAYA_BANK), (float) $payment->biaya_transfer_bank, 0, 'Biaya transfer bank');

        return $this->post("PAY-{$invoice->no_invoice}-{$payment->id}", $payment->tanggal_pembayaran, 'PELUNASAN_HUTANG', $payment->id, "Pembayaran invoice {$invoice->no_invoice}", $lines);
    }

    public function deleteAutomaticJournal(string $source, int $referenceId): void
    {
        Jurnal::where('sumber_transaksi', $source)->where('reff_id', $referenceId)->delete();
    }

    public function reverseAutomaticJournal(string $source, int $referenceId, string $reason): Jurnal
    {
        if (!in_array($source, ['INVOICE_SUPPLIER', 'PELUNASAN_HUTANG'], true)) {
            throw new RuntimeException('Jurnal otomatis harus dibatalkan melalui workflow dokumen sumber.');
        }
        $this->periods->assertOpen(now(), 'Pembatalan dokumen sumber');
        $original = Jurnal::with('details')->where('sumber_transaksi', $source)
            ->where('reff_id', $referenceId)->where('status', 'POSTED')->firstOrFail();
        $reversal = Jurnal::create([
            'no_jurnal' => $this->numbers->financial('JR', now()),
            'tanggal' => now()->toDateString(),
            'keterangan' => $reason,
            'sumber_transaksi' => 'REVERSAL',
            'reff_id' => $original->id,
            'status' => 'POSTED',
            'created_by' => Auth::id(),
            'posted_by' => Auth::id(),
            'posted_at' => now(),
            'reversal_of_id' => $original->id,
            'total_debit' => $original->total_kredit,
            'total_kredit' => $original->total_debit,
        ]);
        $reversal->details()->createMany($original->details->map(fn($line) => [
            'coa_id' => $line->coa_id,
            'debit' => $line->kredit,
            'kredit' => $line->debit,
            'keterangan' => 'Pembalik: ' . $line->keterangan,
        ])->all());
        $original->update(['status' => 'REVERSED']);
        return $reversal;
    }

    private function post(string $number, $date, string $source, int $referenceId, string $description, array $lines): Jurnal
    {
        $lines = collect($lines)->filter(fn($line) => (float) $line['debit'] > 0 || (float) $line['kredit'] > 0)->values();
        $debit = round($lines->sum('debit'), 2);
        $credit = round($lines->sum('kredit'), 2);
        if ($debit <= 0 || abs($debit - $credit) > 0.01) {
            throw new RuntimeException("Jurnal {$number} tidak seimbang (debit {$debit}, kredit {$credit}).");
        }

        $existing = Jurnal::where('sumber_transaksi', $source)->where('reff_id', $referenceId)->first();
        $journal = Jurnal::updateOrCreate(
            ['sumber_transaksi' => $source, 'reff_id' => $referenceId],
            [
                'no_jurnal' => $existing?->no_jurnal ?? $this->numbers->financial('JR', $date),
                'tanggal' => $date,
                'keterangan' => $description,
                'status' => 'POSTED',
                'created_by' => Auth::id(),
                'posted_by' => Auth::id(),
                'posted_at' => now(),
                'total_debit' => $debit,
                'total_kredit' => $credit
            ]
        );
        $journal->details()->delete();
        $journal->details()->createMany($lines->all());
        return $journal;
    }

    private function line(array &$lines, ?int $accountId, float $debit, float $credit, string $description): void
    {
        if ($debit <= 0 && $credit <= 0) {
            return;
        }
        if (!$accountId) {
            throw new RuntimeException("Mapping akun untuk {$description} belum diatur.");
        }
        $lines[] = ['coa_id' => $accountId, 'debit' => round($debit, 2), 'kredit' => round($credit, 2), 'keterangan' => $description];
    }

    private function assertCategoryMapping($category): void
    {
        if (!$category || !$category->coa_persediaan_id || !$category->coa_beban_id || !$category->coa_clearing_lpb_id) {
            throw new RuntimeException('Mapping Persediaan, Pemakaian, dan GRNI pada kategori bahan belum lengkap.');
        }
        ChartOfAccount::assertUsable($category->coa_persediaan_id, [['ASET', 'DEBIT']], 'persediaan kategori bahan');
        ChartOfAccount::assertUsable($category->coa_beban_id, [['BEBAN', 'DEBIT']], 'pemakaian kategori bahan');
        ChartOfAccount::assertUsable($category->coa_clearing_lpb_id, [['LIABILITAS', 'KREDIT']], 'GRNI kategori bahan');
    }
}
