<?php

namespace App\Http\Controllers;

use App\Models\InvoiceLpb;
use App\Models\InvoicePayment;
use App\Models\Jurnal;
use App\Http\Requests\StoreInvoicePaymentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\WmsAccountingService;
use App\Services\PaymentAllocationService;
use App\Models\ChartOfAccount;
use App\Services\DocumentNumberService;

class InvoicePaymentController extends Controller
{
    public function __construct(
        private WmsAccountingService $accounting,
        private PaymentAllocationService $paymentAllocation,
        private DocumentNumberService $numbers
    ) {}

    public function availableAdvances(int $supplierId)
    {
        $this->authorize('viewAny', InvoicePayment::class);

        $advances = InvoicePayment::with('invoice')
            ->where('status', InvoicePayment::POSTED)
            ->where('jenis_selisih', 'UANG_MUKA_SUPPLIER')
            ->whereHas('invoice', fn($query) => $query->where('kode_supplier', $supplierId))
            ->get()
            ->map(fn(InvoicePayment $payment) => [
                'id'                 => $payment->id,
                'payment_number'     => $payment->payment_number,
                'tanggal_pembayaran' => optional($payment->tanggal_pembayaran)->format('Y-m-d'),
                'no_invoice_asal'    => $payment->invoice->no_invoice ?? '-',
                'sisa'               => $payment->sisaUangMuka(),
            ])
            ->filter(fn($row) => $row['sisa'] > 0.01)
            ->values();

        return response()->json(['success' => true, 'data' => $advances]);
    }

    public function store(StoreInvoicePaymentRequest $request)
    {
        $this->authorize('create', InvoicePayment::class);

        $validated = $request->validated();
        $user = Auth::user();

        $invoiceDetail = DB::transaction(function () use ($validated, $user) {
            $invoice = InvoiceLpb::lockForUpdate()->findOrFail($validated['invoice_lpb_id']);
            abort_if($invoice->status === InvoiceLpb::VOID || (float) $invoice->sisa_tagihan <= 0, 422, 'Invoice sudah batal atau tidak memiliki sisa tagihan.');

            $cashAndTax = (float) $validated['jumlah_pembayaran'] + (float) $validated['potongan_pph23'];
            $maximumPph = round((float) $invoice->dasar_pph * (float) $invoice->tarif_pph / 100, 2);
            $recordedPph = (float) $invoice->payments()->sum('potongan_pph23');
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

            $advanceSourceId = $validated['uang_muka_sumber_payment_id'] ?? null;
            $advanceUsed = $advanceSourceId ? round((float) $validated['uang_muka_dipakai'], 2) : 0.0;
            if ($advanceSourceId) {
                $advanceSource = InvoicePayment::lockForUpdate()->with('invoice')->findOrFail($advanceSourceId);
                abort_if(
                    $advanceSource->status !== InvoicePayment::POSTED
                        || $advanceSource->jenis_selisih !== 'UANG_MUKA_SUPPLIER'
                        || !$advanceSource->invoice
                        || (int) $advanceSource->invoice->kode_supplier !== (int) $invoice->kode_supplier,
                    422,
                    'Sumber uang muka tidak valid untuk invoice/supplier ini.'
                );
                abort_if(
                    $advanceUsed > $advanceSource->sisaUangMuka() + 0.01,
                    422,
                    'Nominal uang muka yang dipakai melebihi sisa uang muka supplier yang tersedia.'
                );
            }

            $remainingAfterAdvance = max(0, round((float) $invoice->sisa_tagihan - $advanceUsed, 2));
            abort_if(
                (float) $invoice->sisa_tagihan - $advanceUsed < -0.01,
                422,
                'Uang muka yang dipakai melebihi sisa tagihan invoice.'
            );

            $hasCashActivity = (float) $validated['jumlah_pembayaran'] > 0
                || (float) $validated['potongan_pph23'] > 0
                || $difference > 0;

            if ($hasCashActivity) {
                $allocation = $this->paymentAllocation->calculate(
                    $remainingAfterAdvance,
                    (float) $validated['jumlah_pembayaran'],
                    (float) $validated['potongan_pph23'],
                    $difference,
                    $differenceType
                );
                $pengurangHutangCash = $allocation['ap_reduction'];
                $advance = $allocation['advance'];
            } else {
                abort_if($advanceUsed <= 0, 422, 'Nominal pembayaran tidak boleh nol.');
                $pengurangHutangCash = 0.0;
                $advance = 0.0;
            }

            $pengurangHutang = round($pengurangHutangCash + $advanceUsed, 2);
            abort_if(
                $differenceType === 'UANG_MUKA_SUPPLIER' && abs($difference - $advance) > 0.01,
                422,
                'Nominal selisih harus sama dengan kelebihan pembayaran yang menjadi uang muka supplier.'
            );

            $payment = InvoicePayment::create([
                'payment_number'                   => $validated['payment_number'],
                'invoice_lpb_id'                   => $invoice->id,
                'tanggal_pembayaran'                => $validated['tanggal_pembayaran'],
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
                'uang_muka_sumber_payment_id'       => $advanceSourceId,
                'uang_muka_dipakai'                => $advanceUsed,
                'total_transaksi_pengurang_hutang'  => $pengurangHutang,
                'keterangan'                        => $validated['keterangan'] ?? null,
                'finance_user_id'                   => $user->id,
            ]);

            $totalBayarAkumulasi = $invoice->payments()->sum('total_transaksi_pengurang_hutang');
            $sisaTagihan = $invoice->grand_total - $totalBayarAkumulasi;

            $statusCode = InvoiceLpb::paymentStatus((float) $invoice->grand_total, (float) $totalBayarAkumulasi);
            $sisaTagihan = max(0, $sisaTagihan);

            $invoice->update([
                'total_pembayaran'  => $totalBayarAkumulasi,
                'sisa_tagihan'      => $sisaTagihan,
                'status'            => $statusCode,
                'pph'               => $invoice->payments()->sum('potongan_pph23'),
            ]);

            $this->accounting->postPayment($payment);

            return $payment;
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaksi pembayaran berhasil dicatat dan diposting ke Jurnal COA.',
            'data'    => $invoiceDetail,
            'next_document_number' => $this->numbers->financial('PY'),
        ], 201);
    }

    public function destroy(InvoicePayment $payment)
    {
        $payment->load('invoice');
        $invoice = $payment->invoice;

        $this->authorize('voidPayment', $invoice);

        abort_if($payment->status === InvoicePayment::VOID, 422, 'Pembayaran ini sudah dibatalkan sebelumnya.');
        abort_if(
            $payment->pemakaianUangMuka()->exists(),
            422,
            'Uang muka dari pembayaran ini sudah dipakai pada pembayaran lain; batalkan pembayaran yang memakai uang muka tersebut terlebih dahulu.'
        );

        DB::transaction(function () use ($payment, $invoice) {
            $this->accounting->reverseAutomaticJournal(
                'PELUNASAN_HUTANG',
                $payment->id,
                'Pembatalan pembayaran invoice ' . $invoice->no_invoice
            );
            $payment->update([
                'status' => InvoicePayment::VOID,
                'voided_by' => Auth::id(),
                'voided_at' => now(),
                'void_reason' => 'Dibatalkan melalui sistem',
            ]);

            $totalBayarAkumulasi = $invoice->payments()->sum('total_transaksi_pengurang_hutang');
            $sisaTagihan = $invoice->grand_total - $totalBayarAkumulasi;

            $statusCode = InvoiceLpb::paymentStatus((float) $invoice->grand_total, (float) $totalBayarAkumulasi);
            $sisaTagihan = max(0, $sisaTagihan);

            $invoice->update([
                'total_pembayaran'  => $totalBayarAkumulasi,
                'sisa_tagihan'      => $sisaTagihan,
                'status'            => $statusCode,
                'pph'               => $invoice->payments()->sum('potongan_pph23'),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Detail riwayat pembayaran berhasil dihapus.'
        ]);
    }

}
