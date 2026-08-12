<?php

namespace App\Http\Controllers;

use App\Models\AccountingReconciliation;
use App\Models\AccountingSetting;
use App\Models\Jurnal;
use App\Models\InvoiceLpb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', AccountingReconciliation::class);
        $financial = $request->user()->can('viewFinancials', AccountingReconciliation::class);

        $stock = DB::table('bahans')
            ->leftJoinSub(
                DB::table('inventory_layers')->select('bahan_id')
                    ->selectRaw('SUM(remaining_quantity) layer_quantity')
                    ->selectRaw('SUM(remaining_quantity * unit_cost) inventory_value')
                    ->groupBy('bahan_id'),
                'layers',
                'layers.bahan_id',
                '=',
                'bahans.id'
            )
            ->selectRaw('COUNT(*) total_rows')
            ->selectRaw('SUM(CASE WHEN ABS(COALESCE(bahans.stok_onhand,0)-COALESCE(layers.layer_quantity,0)) <= 0.000001 THEN 0 ELSE 1 END) invalid_rows')
            ->selectRaw('SUM(COALESCE(layers.inventory_value,0)) inventory_value')
            ->first();

        $journal = DB::table('jurnals')->whereIn('status', ['POSTED', 'REVERSED'])
            ->selectRaw('COUNT(*) total_rows')
            ->selectRaw('SUM(CASE WHEN ABS(COALESCE(total_debit,0)-COALESCE(total_kredit,0)) <= 0.01 THEN 0 ELSE 1 END) invalid_rows')
            ->first();

        $invoice = DB::table('invoice_lpbs')->where('status', '!=', InvoiceLpb::VOID)
            ->selectRaw('COUNT(*) total_rows')
            ->selectRaw('SUM(CASE WHEN ABS(COALESCE(sisa_tagihan,0) - GREATEST(COALESCE(grand_total,0)-COALESCE(total_pembayaran,0),0)) <= 0.01 THEN 0 ELSE 1 END) invalid_rows')
            ->selectRaw('SUM(COALESCE(sisa_tagihan,0)) outstanding')
            ->first();

        $grniExpected = (float) DB::table('lpb_details')
            ->join('lpbs', 'lpbs.id_lpb', '=', 'lpb_details.id_lpb')
            ->leftJoin('invoice_lpb_receipts', 'invoice_lpb_receipts.lpb_id', '=', 'lpbs.id')
            ->whereNull('invoice_lpb_receipts.id')
            ->sum(DB::raw('lpb_details.jumlah_barang_diterima * lpb_details.harga'));
        $grniAccounts = DB::table('kategori_bahans')->whereNotNull('coa_clearing_lpb_id')
            ->distinct()->pluck('coa_clearing_lpb_id');
        $grniLedger = (float) DB::table('jurnal_details')->join('jurnals', 'jurnals.id', '=', 'jurnal_details.jurnal_id')
            ->whereIn('jurnals.status', ['POSTED', 'REVERSED'])->whereIn('jurnal_details.coa_id', $grniAccounts)
            ->selectRaw('COALESCE(SUM(kredit-debit),0) balance')->value('balance');

        $apLedger = null;
        try {
            $apId = AccountingSetting::accountId(AccountingSetting::HUTANG_USAHA);
            $apLedger = (float) DB::table('jurnal_details')->join('jurnals', 'jurnals.id', '=', 'jurnal_details.jurnal_id')
                ->whereIn('jurnals.status', ['POSTED', 'REVERSED'])->where('jurnal_details.coa_id', $apId)
                ->selectRaw('COALESCE(SUM(kredit-debit),0) balance')->value('balance');
        } catch (\RuntimeException) {
            $apLedger = null;
        }

        $checks = collect([
            [
                'key' => 'stock',
                'label' => 'Stok on hand vs layer',
                'total' => (int) $stock->total_rows,
                'invalid' => (int) $stock->invalid_rows,
                'amount' => $financial ? (float) $stock->inventory_value : null
            ],
            [
                'key' => 'journal',
                'label' => 'Keseimbangan jurnal',
                'total' => (int) $journal->total_rows,
                'invalid' => (int) $journal->invalid_rows,
                'amount' => null
            ],
            [
                'key' => 'invoice',
                'label' => 'Sisa tagihan invoice',
                'total' => (int) $invoice->total_rows,
                'invalid' => (int) $invoice->invalid_rows,
                'amount' => $financial ? (float) $invoice->outstanding : null
            ],
            [
                'key' => 'grni',
                'label' => 'LPB Barang belum ditagih vs saldo GRNI',
                'total' => 1,
                'invalid' => abs($grniExpected - $grniLedger) <= .01 ? 0 : 1,
                'amount' => $financial ? $grniLedger : null,
                'expected' => $financial ? $grniExpected : null
            ],
            [
                'key' => 'ap',
                'label' => 'Invoice belum lunas vs hutang supplier',
                'total' => 1,
                'invalid' => $apLedger !== null && abs((float) $invoice->outstanding - $apLedger) <= .01 ? 0 : 1,
                'amount' => $financial ? $apLedger : null,
                'expected' => $financial ? (float) $invoice->outstanding : null
            ],
        ]);

        return view('accounting_reconciliation.index', compact('checks', 'financial'));
    }

    public function show(Request $request, string $check)
    {
        $this->authorize('viewAny', AccountingReconciliation::class);
        abort_unless(in_array($check, ['stock', 'journal', 'invoice', 'grni', 'ap'], true), 404);
        $financial = $request->user()->can('viewFinancials', AccountingReconciliation::class);

        if ($check === 'stock') {
            $rows = DB::table('bahans')->leftJoinSub(
                DB::table('inventory_layers')->select('bahan_id')
                    ->selectRaw('SUM(remaining_quantity) layer_quantity')
                    ->selectRaw('SUM(remaining_quantity * unit_cost) inventory_value')->groupBy('bahan_id'),
                'layers',
                'layers.bahan_id',
                '=',
                'bahans.id'
            )->select('bahans.id', 'bahans.nama', 'bahans.stok_onhand')
                ->selectRaw('COALESCE(layers.layer_quantity,0) layer_quantity')
                ->selectRaw('COALESCE(bahans.stok_onhand,0)-COALESCE(layers.layer_quantity,0) difference')
                ->selectRaw('COALESCE(layers.inventory_value,0) inventory_value')->orderBy('bahans.nama')->get();
        } elseif ($check === 'journal') {
            $rows = Jurnal::query()->select('id', 'no_jurnal', 'tanggal', 'sumber_transaksi', 'status', 'total_debit', 'total_kredit')
                ->selectRaw('total_debit-total_kredit difference')->latest('tanggal')->get();
        } elseif ($check === 'invoice' || $check === 'ap') {
            $rows = DB::table('invoice_lpbs')->where('status', '!=', InvoiceLpb::VOID)
                ->select('id', 'no_invoice', 'tanggal', 'grand_total', 'total_pembayaran', 'sisa_tagihan', 'status')
                ->selectRaw('sisa_tagihan-GREATEST(grand_total-total_pembayaran,0) difference')->orderByDesc('tanggal')->get();
        } else {
            $goods = DB::table('lpbs')->join('lpb_details', 'lpb_details.id_lpb', '=', 'lpbs.id_lpb')
                ->leftJoin('invoice_lpb_receipts', 'invoice_lpb_receipts.lpb_id', '=', 'lpbs.id')
                ->whereNull('invoice_lpb_receipts.id')
                ->select('lpbs.id', 'lpbs.id_lpb', 'lpbs.tanggal', 'lpbs.no_po')
                ->selectRaw('SUM(lpb_details.jumlah_barang_diterima * lpb_details.harga) amount')
                ->groupBy('lpbs.id', 'lpbs.id_lpb', 'lpbs.tanggal', 'lpbs.no_po')->get();
            $rows = $goods->sortByDesc('tanggal')->values();
        }

        return view('accounting_reconciliation.show', compact('rows', 'check', 'financial'));
    }
}
