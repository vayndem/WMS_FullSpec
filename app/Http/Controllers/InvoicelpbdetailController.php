<?php

namespace App\Http\Controllers;

use App\Models\Invoicelpb;
use App\Models\Invoicelpbdetail;
use App\Models\Jurnal;
use App\Http\Requests\StoreInvoicelpbdetailRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\WmsAccountingService;
use App\Services\PaymentAllocationService;
use App\Models\ChartOfAccount;
use App\Services\DocumentNumberService;

class InvoicelpbdetailController extends Controller
{
    public function __construct(
        private WmsAccountingService $accounting,
        private PaymentAllocationService $paymentAllocation,
        private DocumentNumberService $numbers
    ) {}
    public function store(StoreInvoicelpbdetailRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();

        $invoiceDetail = DB::transaction(function () use ($validated, $user) {
            $invoice = Invoicelpb::lockForUpdate()->findOrFail($validated['id_invoice_lpb']);
            abort_if($invoice->is_void || (float) $invoice->sisa_tagihan <= 0, 422, 'Invoice sudah batal atau tidak memiliki sisa tagihan.');

            $cashAndTax = (float) $validated['jumlah_pembayaran'] + (float) $validated['potongan_pph23'];
            $maximumPph = round((float) $invoice->dasar_pph * (float) $invoice->tarif_pph / 100, 2);
            $recordedPph = (float) $invoice->details()->sum('potongan_pph23');
            abort_if(
                $recordedPph + (float) $validated['potongan_pph23'] > $maximumPph + 0.01,
                422,
                'Akumulasi PPh 23 melebihi tarif snapshot invoice.'
            );
            $difference = (float) $validated['selisih_bayar'];
            $differenceType = $validated['jenis_selisih'] ?? null;
            if ($difference > 0) {
                $differenceAccount = ChartOfAccount::findOrFail($validated['coa_selisih_id']);
                $required = [
                    'PENDAPATAN_SELISIH' => ['PENDAPATAN', 'KREDIT'],
                    'BEBAN_SELISIH' => ['BEBAN', 'DEBIT'],
                    'UANG_MUKA_SUPPLIER' => ['ASET', 'DEBIT'],
                ][$differenceType] ?? null;
                abort_if(
                    !$required
                        || $differenceAccount->kategori_akun !== $required[0]
                        || $differenceAccount->posisi_normal !== $required[1],
                    422,
                    "Akun yang dipilih harus berkategori {$required[0]} dengan posisi normal {$required[1]}."
                );
            }
            $allocation = $this->paymentAllocation->calculate(
                (float) $invoice->sisa_tagihan,
                (float) $validated['jumlah_pembayaran'],
                (float) $validated['potongan_pph23'],
                $difference,
                $differenceType
            );
            $pengurangHutang = $allocation['ap_reduction'];
            $advance = $allocation['advance'];
            abort_if(
                $differenceType === 'UANG_MUKA_SUPPLIER' && abs($difference - $advance) > 0.01,
                422,
                'Nominal selisih harus sama dengan kelebihan pembayaran yang menjadi uang muka supplier.'
            );

            $detail = Invoicelpbdetail::create([
                'payment_number'                   => $validated['payment_number'],
                'id_invoice_lpb'                   => $invoice->id,
                'tanggal_pembayaran'               => $validated['tanggal_pembayaran'],
                'metode_pembayaran'                => $validated['metode_pembayaran'],
                'coa_kas_bank_id'                  => $validated['coa_kas_bank_id'],
                'jumlah_pembayaran'                => $validated['jumlah_pembayaran'],
                'potongan_pph23'                   => $validated['potongan_pph23'],
                'potongan_materai'                 => $validated['potongan_materai'],
                'biaya_transfer_bank'              => $validated['biaya_transfer_bank'],
                'selisih_bayar'                    => $validated['selisih_bayar'],
                'jenis_selisih'                    => $differenceType,
                'coa_selisih_id'                   => $validated['coa_selisih_id'] ?? null,
                'kelebihan_pembayaran'             => $advance,
                'total_transaksi_pengurang_hutang' => $pengurangHutang,
                'keterangan'                       => $validated['keterangan'] ?? null,
                'id_user_finance'                  => $user->id,
            ]);

            $totalBayarAkumulasi = $invoice->details()->sum('total_transaksi_pengurang_hutang');
            $sisaTagihan = $invoice->grand_total - $totalBayarAkumulasi;

            $statusText = 'Belum Dibayar';
            $statusCode = 0;

            if ($sisaTagihan <= 0) {
                $statusText = 'Lunas';
                $statusCode = 2;
                $sisaTagihan = 0;
            } elseif ($totalBayarAkumulasi > 0) {
                $statusText = 'Dibayar Sebagian';
                $statusCode = 1;
            }

            $invoice->update([
                'total_pembayaran'  => $totalBayarAkumulasi,
                'sisa_tagihan'      => $sisaTagihan,
                'status_pembayaran' => $statusText,
                'status'            => $statusCode,
                'pph'               => $invoice->details()->sum('potongan_pph23'),
            ]);

            $this->accounting->postPayment($detail);

            return $detail;
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaksi pembayaran berhasil dicatat dan diposting ke Jurnal COA.',
            'data'    => $invoiceDetail,
            'next_document_number' => $this->numbers->financial('PY'),
        ], 201);
    }

    public function destroy($id)
    {
        $detail = Invoicelpbdetail::with('invoice')->findOrFail($id);
        $invoice = $detail->invoice;

        $this->authorize('voidPayment', $invoice);

        DB::transaction(function () use ($detail, $invoice) {
            $this->accounting->reverseAutomaticJournal(
                'PELUNASAN_HUTANG',
                $detail->id,
                'Pembatalan pembayaran invoice ' . $invoice->no_invoice
            );
            $detail->update([
                'is_void' => true,
                'voided_by' => Auth::id(),
                'voided_at' => now(),
                'void_reason' => 'Dibatalkan melalui sistem',
            ]);

            $totalBayarAkumulasi = $invoice->details()->sum('total_transaksi_pengurang_hutang');
            $sisaTagihan = $invoice->grand_total - $totalBayarAkumulasi;

            $statusText = 'Belum Dibayar';
            $statusCode = 0;

            if ($sisaTagihan <= 0 && $totalBayarAkumulasi > 0) {
                $statusText = 'Lunas';
                $statusCode = 2;
                $sisaTagihan = 0;
            } elseif ($totalBayarAkumulasi > 0) {
                $statusText = 'Dibayar Sebagian';
                $statusCode = 1;
            }

            $invoice->update([
                'total_pembayaran'  => $totalBayarAkumulasi,
                'sisa_tagihan'      => $sisaTagihan,
                'status_pembayaran' => $statusText,
                'status'            => $statusCode,
                'pph'               => $invoice->details()->sum('potongan_pph23'),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Detail riwayat pembayaran berhasil dihapus.'
        ]);
    }

    private function syncJurnalPembayaran(Invoicelpbdetail $detail): void
    {
        $this->accounting->postPayment($detail);
    }
}
