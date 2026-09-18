<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePenerimaanJasaRequest;
use App\Http\Requests\CancelPenerimaanJasaRequest;
use App\Models\PenerimaanBarang;
use App\Models\PenerimaanJasa;
use App\Models\PesananJasa;
use App\Models\PesananJasaDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\DocumentNumberService;
use App\Exports\GenericTableExport;
use Maatwebsite\Excel\Facades\Excel;

class PenerimaanJasaController extends Controller
{
    public function __construct(private DocumentNumberService $numbers) {}
    public function index(Request $request)
    {
        $this->authorize('viewAny', PenerimaanJasa::class);
        $query = PenerimaanJasa::with(['pembelian.supplier', 'invoiceReceipts'])->when($request->filled('q'), fn($q) => $q->where('id_lpb', 'like', '%' . $request->q . '%'))
            ->latest('tanggal');
        $perPage = $this->perPage($request, $query->count());
        $baps = $query->paginate($perPage)->withQueryString();
        return view('penerimaan_jasa.index', compact('baps'));
    }
    private function perPage(Request $request, int $total): int
    {
        $value = strtolower((string)$request->input('per_page', 10));
        return $value === 'all' ? max(1, $total) : (in_array((int)$value, [10, 20, 50, 100], true) ? (int)$value : 10);
    }
    public function reportPdf(Request $request)
    {
        $this->authorize('viewAny', PenerimaanJasa::class);
        $financial = $request->user()->can('viewFinancials', PenerimaanJasa::class);
        $query = PenerimaanJasa::with(['pembelian.supplier', 'invoiceReceipts'])->when($request->filled('q'), fn($q) => $q->where('id_lpb', 'like', '%' . $request->q . '%'));
        if ($financial) $query->withSum('serviceDetails', 'amount');
        $rows = $query->get()->map(function ($b) use ($financial) {
            $row = [
                'number' => $b->id_lpb,
                'date' => $b->tanggal->format('d-m-Y'),
                'po' => $b->no_po,
                'supplier' => $b->pembelian->supplier->nama,
                'status' => $b->status === PenerimaanBarang::CANCELLED ? 'Dibatalkan' : ($b->invoiceReceipts->isNotEmpty() ? 'Selesai / Sudah Invoice' : 'Sedang Dikerjakan'),
            ];
            if ($financial) $row['amount'] = 'Rp ' . number_format($b->service_details_sum_amount, 0, ',', '.');
            return $row;
        });
        $columns = [['key' => 'number', 'label' => 'No BAP'], ['key' => 'date', 'label' => 'Tanggal'], ['key' => 'po', 'label' => 'PO Jasa'], ['key' => 'supplier', 'label' => 'Supplier']];
        if ($financial) $columns[] = ['key' => 'amount', 'label' => 'Nilai', 'align' => 'right'];
        $columns[] = ['key' => 'status', 'label' => 'Status'];
        return Pdf::loadView('reports.table-pdf', ['title' => 'Daftar BAP Jasa', 'columns' => $columns, 'rows' => $rows, 'search' => $request->q, 'filters' => collect(), 'generatedAt' => now()])->setPaper('a4', 'landscape')->stream('bap-jasa.pdf');
    }

    public function reportExcel(Request $request)
    {
        $this->authorize('viewAny', PenerimaanJasa::class);
        $financial = $request->user()->can('viewFinancials', PenerimaanJasa::class);
        $query = PenerimaanJasa::with(['pembelian.supplier', 'invoiceReceipts'])->when($request->filled('q'), fn($q) => $q->where('id_lpb', 'like', '%' . $request->q . '%'));
        if ($financial) $query->withSum('serviceDetails', 'amount');
        $rows = $query->get()->map(function ($b) use ($financial) {
            $row = [
                'number' => $b->id_lpb,
                'date' => $b->tanggal->format('d-m-Y'),
                'po' => $b->no_po,
                'supplier' => $b->pembelian->supplier->nama,
                'status' => $b->status === PenerimaanBarang::CANCELLED ? 'Dibatalkan' : ($b->invoiceReceipts->isNotEmpty() ? 'Selesai / Sudah Invoice' : 'Sedang Dikerjakan'),
            ];
            if ($financial) $row['amount'] = (float) $b->service_details_sum_amount;
            return $row;
        });
        $columns = [['key' => 'number', 'label' => 'No BAP'], ['key' => 'date', 'label' => 'Tanggal'], ['key' => 'po', 'label' => 'PO Jasa'], ['key' => 'supplier', 'label' => 'Supplier']];
        if ($financial) $columns[] = ['key' => 'amount', 'label' => 'Nilai'];
        $columns[] = ['key' => 'status', 'label' => 'Status'];

        return Excel::download(new GenericTableExport($columns, $rows), 'bap-jasa-' . now()->format('Ymd-His') . '.xlsx');
    }

    public function create()
    {
        $this->authorize('create', PenerimaanJasa::class);
        $orders = PesananJasa::with(['supplier', 'serviceDetails.category'])
            ->whereDoesntHave(
                'serviceDetails.bapDetails.lpb',
                fn($query) => $query->where('status', PenerimaanBarang::POSTED)
            )
            ->latest('tanggal')->get();
        $documentNumber = $this->numbers->preview(DocumentNumberService::EXTERNAL, 'BAP');
        $financial = request()->user()->can('viewFinancials', PenerimaanJasa::class);
        return view('penerimaan_jasa.create', compact('orders', 'documentNumber', 'financial'));
    }
    public function store(StorePenerimaanJasaRequest $request)
    {
        $bap = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $po = PesananJasa::where('no_po', $data['no_po'])->lockForUpdate()->firstOrFail();
            $bap = PenerimaanJasa::create([
                'id_lpb' => $this->numbers->external('BAP'),
                'tanggal' => $data['tanggal'],
                'no_po' => $po->no_po,
                'no_sj' => $data['no_sj'],
                'id_user' => Auth::id(),
                'status' => PenerimaanBarang::POSTED,
                'jenis_lpb' => 3,
                'kunci' => 1,
                'document_type' => 'SERVICE_BAP'
            ]);
            foreach ($data['items'] as $item) {
                $poDetail = PesananJasaDetail::with('category')->lockForUpdate()->findOrFail($item['service_po_detail_id']);
                if ($poDetail->pembelian_id !== $po->id) throw new RuntimeException('Detail jasa bukan bagian dari PO yang dipilih.');
                $hasActiveBap = $poDetail->bapDetails()
                    ->whereHas('lpb', fn($query) => $query->where('status', PenerimaanBarang::POSTED))
                    ->exists();
                if ((float) $poDetail->accepted_amount > .01 || $hasActiveBap) {
                    throw new RuntimeException("Pekerjaan {$poDetail->description} sudah mempunyai BAP.");
                }
                $detail = $bap->serviceDetails()->create(
                    collect($item)->except('allocations')->all() + ['id_kategori' => $poDetail->id_kategori]
                );
                $allocations = $item['allocations'] ?? [];
                $allocated = 0;
                foreach ($allocations as $index => $allocation) {
                    $amount = $index === array_key_last($allocations) ? round((float)$item['amount'] - $allocated, 2) : round((float)$item['amount'] * (float)$allocation['percentage'] / 100, 2);
                    $detail->allocations()->create($allocation + ['amount' => $amount]);
                    $allocated += $amount;
                }
                $poDetail->update(['accepted_amount' => $poDetail->subtotal]);
            }
            return $bap;
        });
        return redirect()->route('penerimaan-jasa.show', $bap)
            ->with('success', 'BAP jasa dibuat. Pekerjaan berstatus sedang berjalan dan belum membentuk jurnal.');
    }
    public function show(PenerimaanJasa $serviceBap)
    {
        $this->authorize('view', $serviceBap);
        $serviceBap->load(['pembelian.supplier', 'serviceDetails.servicePoDetail.category', 'serviceDetails.kategori', 'serviceDetails.allocations', 'invoiceReceipts.invoice']);
        $financial = request()->user()->can('viewFinancials', PenerimaanJasa::class);
        return view('penerimaan_jasa.show', ['bap' => $serviceBap, 'financial' => $financial]);
    }
    public function cancel(CancelPenerimaanJasaRequest $request, PenerimaanJasa $serviceBap)
    {
        DB::transaction(function () use ($request, $serviceBap) {
            foreach ($serviceBap->serviceDetails()->lockForUpdate()->get() as $detail) {
                $detail->servicePoDetail()->update(['accepted_amount' => 0]);
            }
            $serviceBap->update(['status' => PenerimaanBarang::CANCELLED, 'cancelled_by' => Auth::id(), 'cancelled_at' => now(), 'cancellation_reason' => $request->validated('reason')]);
        });
        return back()->with('success', 'BAP jasa dibatalkan. Tidak ada jurnal yang perlu direversal.');
    }
}
