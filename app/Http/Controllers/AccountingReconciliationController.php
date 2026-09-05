<?php

namespace App\Http\Controllers;

use App\Models\AccountingReconciliation;
use App\Models\FakturPembelian;
use App\Services\AccountingReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingReconciliationController extends Controller
{
    public function __construct(private AccountingReconciliationService $reconciliation) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', AccountingReconciliation::class);
        $financial = $request->user()->can('viewFinancials', AccountingReconciliation::class);

        $checks = $this->reconciliation->checks()->map(fn($check) => [
            ...$check,
            'amount' => $financial ? $check['amount'] : null,
            'expected' => $financial ? $check['expected'] : null,
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
                DB::table('wms_layer_persediaan')->select('bahan_id')
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
            $rows = DB::table('wms_faktur_pembelian')->where('status', '!=', FakturPembelian::VOID)
                ->where('status', '!=', FakturPembelian::PENDING_APPROVAL)
                ->select('id', 'no_invoice', 'tanggal', 'grand_total', 'total_pembayaran', 'sisa_tagihan', 'status')
                ->selectRaw('sisa_tagihan-GREATEST(grand_total-total_pembayaran,0) difference')->orderByDesc('tanggal')->get();
        } else {
            $goods = DB::table('wms_penerimaan_barang')->join('wms_penerimaan_barang_detail', 'wms_penerimaan_barang_detail.id_lpb', '=', 'wms_penerimaan_barang.id_lpb')
                ->leftJoin('wms_faktur_pembelian_penerimaan', 'wms_faktur_pembelian_penerimaan.lpb_id', '=', 'wms_penerimaan_barang.id')
                ->leftJoin('wms_faktur_pembelian', 'wms_faktur_pembelian.id', '=', 'wms_faktur_pembelian_penerimaan.invoice_lpb_id')
                ->where(fn($query) => $query->whereNull('wms_faktur_pembelian_penerimaan.id')
                    ->orWhere('wms_faktur_pembelian.status', FakturPembelian::PENDING_APPROVAL))
                ->select('wms_penerimaan_barang.id', 'wms_penerimaan_barang.id_lpb', 'wms_penerimaan_barang.tanggal', 'wms_penerimaan_barang.no_po')
                ->selectRaw('SUM(wms_penerimaan_barang_detail.jumlah_barang_diterima * wms_penerimaan_barang_detail.harga) amount')
                ->groupBy('wms_penerimaan_barang.id', 'wms_penerimaan_barang.id_lpb', 'wms_penerimaan_barang.tanggal', 'wms_penerimaan_barang.no_po')->get();
            $rows = $goods->sortByDesc('tanggal')->values();
        }

        return view('accounting_reconciliation.show', compact('rows', 'check', 'financial'));
    }
}
