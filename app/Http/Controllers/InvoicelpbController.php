<?php

namespace App\Http\Controllers;

use App\Models\Invoicelpb;
use App\Models\Lpb;
use App\Models\Jurnal;
use App\Http\Requests\StoreInvoicelpbRequest;
use App\Http\Requests\UpdateInvoicelpbRequest;
use App\Policies\InvoicelpbPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\WmsAccountingService;
use App\Models\TaxRate;
use App\Models\Supplier;
use App\Services\DocumentNumberService;

class InvoicelpbController extends Controller
{
    public function __construct(private WmsAccountingService $accounting, private DocumentNumberService $numbers) {}
    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoicelpb::class);

        if ($request->ajax()) {
            $paymentStatus = $request->input('payment_status');
            $query = Invoicelpb::with(['supplier'])
                ->when(
                    in_array((string) $paymentStatus, ['0', '1', '2'], true),
                    fn($query) => $query->where('status', (int) $paymentStatus)
                )
                ->when($request->filled('focus'), fn($query) => $query->whereKey($request->integer('focus')));

            return datatables()->of($query)
                ->filterColumn('supplier_nama', function ($query, $keyword) {
                    $query->whereHas('supplier', function ($supplier) use ($keyword) {
                        $supplier->where('nama', 'like', "%{$keyword}%");
                    });
                })
                ->addIndexColumn()
                ->addColumn('supplier_nama', function ($row) {
                    return $row->supplier->nama ?? '-';
                })
                ->addColumn('can_update', function ($row) use ($request) {
                    return $request->user()->can('update', $row);
                })
                ->addColumn('can_delete', function ($row) use ($request) {
                    return $request->user()->can('delete', $row);
                })
                ->addColumn('can_pay', function ($row) use ($request) {
                    return $request->user()->can('pay', $row);
                })
                ->make(true);
        }

        $paymentNumber = $request->user()->isFinance()
            ? $this->numbers->financial('PY')
            : null;
        return view('invoice_lpb.index', compact('paymentNumber'));
    }

    public function reportPdf(Request $request)
    {
        $this->authorize('viewAny', Invoicelpb::class);

        $filters = collect($request->input('filters', []))->filter(fn($value) => $value !== '');
        $search = trim((string) $request->input('search', ''));
        $query = Invoicelpb::with('supplier')->latest('tanggal');

        if ($search !== '') {
            $query->where(fn($q) => $q->where('no_invoice', 'like', "%{$search}%")
                ->orWhere('tanggal', 'like', "%{$search}%")
                ->orWhere('tgl_deadline_pembayaran', 'like', "%{$search}%")
                ->orWhere('status_pembayaran', 'like', "%{$search}%")
                ->orWhereHas('supplier', fn($supplier) => $supplier->where('nama', 'like', "%{$search}%")));
        }

        foreach (['no_invoice', 'tanggal', 'tgl_deadline_pembayaran', 'grand_total', 'sisa_tagihan', 'status_pembayaran'] as $field) {
            if ($filters->has($field)) {
                $query->where($field, 'like', "%{$filters[$field]}%");
            }
        }
        if ($filters->has('supplier_nama')) {
            $query->whereHas('supplier', fn($supplier) => $supplier->where('nama', 'like', "%{$filters['supplier_nama']}%"));
        }

        $rows = $query->limit(5000)->get()->map(fn($row) => [
            'no_invoice' => $row->no_invoice,
            'tanggal' => $row->tanggal,
            'supplier_nama' => $row->supplier->nama ?? '-',
            'tgl_deadline_pembayaran' => $row->tgl_deadline_pembayaran ?: '-',
            'grand_total' => 'Rp ' . number_format($row->grand_total, 0, ',', '.'),
            'sisa_tagihan' => 'Rp ' . number_format($row->sisa_tagihan, 0, ',', '.'),
            'status_pembayaran' => $row->status_pembayaran,
        ]);

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Daftar Invoice LPB',
            'columns' => [
                ['key' => 'no_invoice', 'label' => 'No Invoice', 'align' => 'left'],
                ['key' => 'tanggal', 'label' => 'Tanggal', 'align' => 'left'],
                ['key' => 'supplier_nama', 'label' => 'Supplier', 'align' => 'left'],
                ['key' => 'tgl_deadline_pembayaran', 'label' => 'Deadline', 'align' => 'left'],
                ['key' => 'grand_total', 'label' => 'Grand Total', 'align' => 'right'],
                ['key' => 'sisa_tagihan', 'label' => 'Sisa Tagihan', 'align' => 'right'],
                ['key' => 'status_pembayaran', 'label' => 'Status', 'align' => 'left'],
            ],
            'rows' => $rows,
            'search' => $search,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream('daftar-invoice-lpb.pdf');
    }

    public function create()
    {
        $this->authorize('create', Invoicelpb::class);
        $lpbs = Lpb::whereNull('no_invoice')->where('is_cancelled', false)->where('status', 1)
            ->with(['pembelian.supplier', 'details', 'serviceDetails'])->orderBy('id_lpb', 'desc')->get();
        $supplierIds = $lpbs->pluck('pembelian.supplier_id')->filter()->unique()->values();
        $suppliers = Supplier::whereIn('id', $supplierIds)->orderBy('nama')->get();
        return view('invoice_lpb.create', compact('lpbs', 'suppliers'));
    }

    public function getLpbDetail($id_lpb)
    {
        $this->authorize('create', Invoicelpb::class);
        $lpb = Lpb::where('id_lpb', $id_lpb)
            ->whereNull('no_invoice')
            ->where('is_cancelled', false)
            ->where('status', 1)
            ->with(['details.bahan', 'serviceDetails.servicePoDetail.category', 'serviceDetails.allocations', 'pembelian.supplier'])
            ->firstOrFail();

        $subTotal = 0;
        $items = $lpb->details->map(function ($detail) use (&$subTotal) {
            $totalHarga = $detail->jumlah_barang_diterima * ($detail->harga ?? 0);
            $subTotal += $totalHarga;

            return [
                'id'                     => $detail->id,
                'id_bahan'               => $detail->id_bahan,
                'nama_bahan'             => $detail->bahan->nama ?? '-',
                'jumlah_barang_diterima' => $detail->jumlah_barang_diterima,
                'harga'                  => $detail->harga ?? 0,
                'total_harga'            => $totalHarga,
            ];
        });
        foreach ($lpb->serviceDetails as $detail) {
            $subTotal += (float) $detail->amount;
            $items->push([
                'id' => $detail->id,
                'id_bahan' => null,
                'nama_bahan' => '[JASA] ' . $detail->servicePoDetail->description,
                'jumlah_barang_diterima' => '100% selesai',
                'harga' => $detail->amount,
                'total_harga' => $detail->amount,
            ]);
        }

        return response()->json([
            'success'   => true,
            'lpb'       => $lpb,
            'sub_total' => $subTotal,
            'items'     => $items
        ]);
    }

    public function store(StoreInvoicelpbRequest $request)
    {
        $validated = $request->validated();

        $invoice = DB::transaction(function () use ($validated) {
            $lpbs = Lpb::whereIn('id', $validated['lpb_ids'])->whereNull('no_invoice')
                ->where('is_cancelled', false)->where('status', 1)
                ->with(['details', 'serviceDetails', 'pembelian'])->lockForUpdate()->get();
            if ($lpbs->count() !== count($validated['lpb_ids'])) {
                throw new \RuntimeException('Salah satu LPB sudah digunakan invoice lain.');
            }
            $supplierIds = $lpbs->pluck('pembelian.supplier_id')->unique();
            if ($supplierIds->count() !== 1 || (int) $supplierIds->first() !== (int) $validated['kode_supplier']) {
                throw new \RuntimeException('Semua LPB dalam satu invoice harus berasal dari supplier yang sama.');
            }
            $subTotal = $lpbs->sum(fn($lpb) => $this->receiptAmount($lpb));

            $ppnPercent = $validated['is_ppn'] ? TaxRate::rateFor('PPN', $validated['tanggal']) : 0;
            $pphPercent = TaxRate::rateFor('PPH23', $validated['tanggal']);
            $ppnNominal = round(($subTotal * $ppnPercent) / 100, 2);

            $grandTotal = ($subTotal + $ppnNominal + $validated['ongkir']) - $validated['diskon'];

            $createdInvoice = Invoicelpb::create([
                'no_invoice'              => $validated['no_invoice'],
                'kode_supplier'           => $validated['kode_supplier'],
                'tanggal'                 => $validated['tanggal'],
                'tgl_deadline_pembayaran' => $validated['tgl_deadline_pembayaran'] ?? null,
                'sub_total'               => $subTotal,
                'jenis_pajak'             => $validated['is_ppn'] ? 'PPN' : 'NON_PPN',
                'dpp_ppn'                 => $validated['is_ppn'] ? $subTotal : 0,
                'tarif_ppn'               => $ppnPercent,
                'ppn'                     => $ppnNominal,
                'dasar_pph'               => $subTotal,
                'tarif_pph'               => $pphPercent,
                'diskon'                  => $validated['diskon'],
                'ongkir'                  => $validated['ongkir'],
                'pph'                     => 0,
                'grand_total'             => $grandTotal,
                'status_pembayaran'       => 'Belum Dibayar',
                'total_pembayaran'        => 0,
                'sisa_tagihan'            => $grandTotal,
                'note'                    => $validated['note'] ?? null,
                'status'                  => 0,
            ]);

            foreach ($lpbs as $lpb) {
                $createdInvoice->receipts()->create([
                    'lpb_id' => $lpb->id,
                    'amount' => $this->receiptAmount($lpb),
                ]);
                $lpb->update(['no_invoice' => $validated['no_invoice']]);
                if ($lpb->document_type === 'SERVICE_BAP') {
                    $lpb->pembelian()->update(['status' => 2]);
                }
            }

            $this->accounting->postInvoice($createdInvoice);

            return $createdInvoice;
        });

        return response()->json([
            'success' => true,
            'message' => 'Invoice LPB berhasil dibuat dan dicatat ke Jurnal COA (Hutang Usaha).',
            'data'    => $invoice
        ], 201);
    }

    public function show($id)
    {
        $invoice = Invoicelpb::with(['supplier', 'details.userFinance', 'details.coaKasBank', 'details.coaSelisih', 'lpbs.details.bahan'])->findOrFail($id);
        $this->authorize('view', $invoice);

        return response()->json([
            'success' => true,
            'data'    => array_merge($invoice->toArray(), [
                'can_update' => request()->user()->can('update', $invoice),
                'can_delete' => request()->user()->can('delete', $invoice),
                'can_pay' => request()->user()->can('pay', $invoice),
            ])
        ]);
    }

    public function edit($id)
    {
        $invoice = Invoicelpb::with('lpbs')->findOrFail($id);
        $this->authorize('update', $invoice);

        $lpbs = Lpb::where('is_cancelled', false)
            ->where('status', 1)
            ->where(fn($query) => $query->whereNull('no_invoice')->orWhereIn('id', $invoice->lpbs->pluck('id')))
            ->whereHas('pembelian', fn($query) => $query->where('supplier_id', $invoice->kode_supplier))
            ->with(['pembelian.supplier', 'serviceDetails'])->get();
        return view('invoice_lpb.edit', compact('invoice', 'lpbs'));
    }

    public function update(UpdateInvoicelpbRequest $request, $id)
    {
        $invoice = Invoicelpb::findOrFail($id);
        $this->authorize('update', $invoice);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $invoice) {
            if ($invoice->details()->exists()) {
                throw new \RuntimeException('Invoice yang sudah memiliki pembayaran tidak boleh diubah.');
            }
            $lpbs = Lpb::whereIn('id', $validated['lpb_ids'])->where('is_cancelled', false)->where('status', 1)
                ->with(['details', 'serviceDetails', 'pembelian'])->lockForUpdate()->get();
            $supplierIds = $lpbs->pluck('pembelian.supplier_id')->unique();
            if (
                $lpbs->count() !== count($validated['lpb_ids'])
                || $supplierIds->count() !== 1
                || (int) $supplierIds->first() !== (int) $validated['kode_supplier']
            ) {
                throw new \RuntimeException('LPB tidak valid atau berasal dari supplier berbeda.');
            }
            foreach ($invoice->lpbs as $oldLpb) {
                $oldLpb->update(['no_invoice' => null]);
                if ($oldLpb->document_type === 'SERVICE_BAP') {
                    $oldLpb->pembelian()->update(['status' => 1]);
                }
            }
            $subTotal = $lpbs->sum(fn($lpb) => $this->receiptAmount($lpb));
            $ppnPercent = $validated['is_ppn'] ? TaxRate::rateFor('PPN', $validated['tanggal']) : 0;
            $pphPercent = TaxRate::rateFor('PPH23', $validated['tanggal']);
            $ppnNominal = round(($subTotal * $ppnPercent) / 100, 2);

            $grandTotal = ($subTotal + $ppnNominal + $validated['ongkir']) - $validated['diskon'];
            $sisaTagihan = $grandTotal - $invoice->total_pembayaran;

            $statusText = 'Belum Dibayar';
            $statusCode = 0;

            if ($sisaTagihan <= 0) {
                $statusText = 'Lunas';
                $statusCode = 2;
                $sisaTagihan = 0;
            } elseif ($invoice->total_pembayaran > 0) {
                $statusText = 'Dibayar Sebagian';
                $statusCode = 1;
            }

            $invoice->update([
                'no_invoice'              => $validated['no_invoice'],
                'kode_supplier'           => $validated['kode_supplier'],
                'tanggal'                 => $validated['tanggal'],
                'tgl_deadline_pembayaran' => $validated['tgl_deadline_pembayaran'] ?? null,
                'sub_total'               => $subTotal,
                'jenis_pajak'             => $validated['is_ppn'] ? 'PPN' : 'NON_PPN',
                'dpp_ppn'                 => $validated['is_ppn'] ? $subTotal : 0,
                'tarif_ppn'               => $ppnPercent,
                'ppn'                     => $ppnNominal,
                'dasar_pph'               => $subTotal,
                'tarif_pph'               => $pphPercent,
                'diskon'                  => $validated['diskon'],
                'ongkir'                  => $validated['ongkir'],
                'pph'                     => 0,
                'grand_total'             => $grandTotal,
                'sisa_tagihan'            => $sisaTagihan,
                'status_pembayaran'       => $statusText,
                'status'                  => $statusCode,
                'note'                    => $validated['note'] ?? null,
            ]);
            $invoice->receipts()->delete();
            foreach ($lpbs as $lpb) {
                $invoice->receipts()->create([
                    'lpb_id' => $lpb->id,
                    'amount' => $this->receiptAmount($lpb)
                ]);
                $lpb->update(['no_invoice' => $validated['no_invoice']]);
                if ($lpb->document_type === 'SERVICE_BAP') {
                    $lpb->pembelian()->update(['status' => 2]);
                }
            }
            $this->accounting->postInvoice($invoice->fresh());
        });

        return response()->json([
            'success' => true,
            'message' => 'Invoice LPB berhasil diperbarui.'
        ]);
    }

    public function destroy($id)
    {
        $invoice = Invoicelpb::findOrFail($id);
        $this->authorize('delete', $invoice);

        DB::transaction(function () use ($invoice) {
            if ($invoice->details()->exists()) {
                throw new \RuntimeException('Invoice yang sudah memiliki pembayaran tidak boleh dihapus.');
            }
            $receipts = Lpb::where('no_invoice', $invoice->no_invoice)->get();
            foreach ($receipts as $receipt) {
                $receipt->update(['no_invoice' => null]);
                if ($receipt->document_type === 'SERVICE_BAP') {
                    $receipt->pembelian()->update(['status' => 1]);
                }
            }

            $this->accounting->reverseAutomaticJournal(
                'INVOICE_SUPPLIER',
                $invoice->id,
                'Pembatalan invoice supplier ' . $invoice->no_invoice
            );
            $invoice->receipts()->delete();
            $invoice->update([
                'is_void' => true,
                'voided_by' => auth()->id(),
                'voided_at' => now(),
                'void_reason' => 'Dibatalkan melalui sistem',
                'status_pembayaran' => 'Dibatalkan',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Invoice LPB berhasil dihapus.'
        ]);
    }

    private function syncJurnalInvoice(Invoicelpb $invoice): void
    {
        $this->accounting->postInvoice($invoice);
    }

    private function receiptAmount(Lpb $lpb): float
    {
        return (float) $lpb->details->sum(fn($detail) => (float) $detail->jumlah_barang_diterima * (float) $detail->harga)
            + (float) $lpb->serviceDetails->sum('amount');
    }
}
