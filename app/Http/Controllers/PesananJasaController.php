<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePesananJasaRequest;
use App\Models\PesananJasa;
use App\Models\Aset;
use App\Models\KategoriJasa;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\DocumentNumberService;
use App\Exports\GenericTableExport;
use Maatwebsite\Excel\Facades\Excel;

class PesananJasaController extends Controller
{
    public function __construct(private DocumentNumberService $numbers) {}
    public function index(Request $request)
    {
        $this->authorize('viewAny', PesananJasa::class);
        $query = PesananJasa::with('supplier')->withSum('serviceDetails', 'subtotal')
            ->when($request->filled('q'), fn($q) => $q->where(fn($x) => $x->where('no_po', 'like', '%' . $request->q . '%')->orWhereHas('supplier', fn($s) => $s->where('nama', 'like', '%' . $request->q . '%'))))
            ->latest('tanggal');
        $perPage = $this->perPage($request, $query->count());
        $orders = $query->paginate($perPage)->withQueryString();
        return view('pesanan_jasa.index', compact('orders'));
    }
    private function perPage(Request $request, int $total): int
    {
        $value = strtolower((string)$request->input('per_page', 10));
        return $value === 'all' ? max(1, $total) : (in_array((int)$value, [10, 20, 50, 100], true) ? (int)$value : 10);
    }
    public function reportPdf(Request $request)
    {
        $this->authorize('viewAny', PesananJasa::class);
        $rows = PesananJasa::with('supplier')->withSum('serviceDetails', 'subtotal')->when($request->filled('q'), fn($q) => $q->where('no_po', 'like', '%' . $request->q . '%'))->get()->map(fn($po) => ['number' => $po->no_po, 'date' => $po->tanggal, 'supplier' => $po->supplier->nama, 'amount' => 'Rp ' . number_format($po->service_details_sum_subtotal, 0, ',', '.')]);
        return Pdf::loadView('reports.table-pdf', ['title' => 'Daftar PO Jasa', 'columns' => [['key' => 'number', 'label' => 'No PO'], ['key' => 'date', 'label' => 'Tanggal'], ['key' => 'supplier', 'label' => 'Supplier'], ['key' => 'amount', 'label' => 'Nilai', 'align' => 'right']], 'rows' => $rows, 'search' => $request->q, 'filters' => collect(), 'generatedAt' => now()])->setPaper('a4', 'landscape')->stream('po-jasa.pdf');
    }

    public function reportExcel(Request $request)
    {
        $this->authorize('viewAny', PesananJasa::class);
        $rows = PesananJasa::with('supplier')->withSum('serviceDetails', 'subtotal')->when($request->filled('q'), fn($q) => $q->where('no_po', 'like', '%' . $request->q . '%'))->get()->map(fn($po) => ['number' => $po->no_po, 'date' => $po->tanggal, 'supplier' => $po->supplier->nama, 'amount' => (float) $po->service_details_sum_subtotal]);
        $columns = [['key' => 'number', 'label' => 'No PO'], ['key' => 'date', 'label' => 'Tanggal'], ['key' => 'supplier', 'label' => 'Supplier'], ['key' => 'amount', 'label' => 'Nilai']];

        return Excel::download(new GenericTableExport($columns, $rows), 'po-jasa-' . now()->format('Ymd-His') . '.xlsx');
    }

    public function create()
    {
        $this->authorize('create', PesananJasa::class);
        return view('pesanan_jasa.form', $this->formData() + ['documentNumber' => $this->numbers->preview(DocumentNumberService::FINANCIAL, 'PJ')]);
    }
    public function store(StorePesananJasaRequest $request)
    {
        $po = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $items = $data['items'];
            unset($data['items']);
            $subtotal = collect($items)->sum(fn($x) => (float)$x['quantity'] * (float)$x['unit_price']);
            $data['no_po'] = $this->numbers->financial('PJ');
            $po = PesananJasa::create($data + ['document_type' => 'SERVICE', 'total_exclude' => $subtotal, 'total_include' => $subtotal, 'grand_total' => $subtotal, 'no_order' => '-', 'status' => PesananJasa::OPEN]);
            foreach ($items as $item) {
                $category = KategoriJasa::findOrFail($item['service_category_id']);
                $po->serviceDetails()->create($item + [
                    'id_kategori' => $category->kategori_bahan_id,
                    'service_type' => $category->code,
                    'subtotal' => (float)$item['quantity'] * (float)$item['unit_price'],
                ]);
            }
            return $po;
        });
        return redirect()->route('pesanan-jasa.show', $po)->with('success', 'PO jasa berhasil dibuat.');
    }
    public function show(PesananJasa $servicePurchase)
    {
        $this->authorize('view', $servicePurchase);
        $servicePurchase->load(['supplier', 'serviceDetails.category', 'serviceDetails.kategori', 'serviceDetails.bapDetails.lpb']);
        return view('pesanan_jasa.show', [
            'po' => $servicePurchase,
            'financial' => request()->user()->can('viewFinancials', $servicePurchase),
        ]);
    }
    public function edit(PesananJasa $servicePurchase)
    {
        $this->authorize('update', $servicePurchase);
        return view('pesanan_jasa.form', $this->formData() + ['po' => $servicePurchase->load('serviceDetails')]);
    }
    public function update(StorePesananJasaRequest $request, PesananJasa $servicePurchase)
    {
        DB::transaction(function () use ($request, $servicePurchase) {
            $data = $request->validated();
            $items = $data['items'];
            unset($data['items']);
            $subtotal = collect($items)->sum(fn($x) => (float)$x['quantity'] * (float)$x['unit_price']);
            $servicePurchase->update($data + ['total_exclude' => $subtotal, 'total_include' => $subtotal, 'grand_total' => $subtotal]);
            $servicePurchase->serviceDetails()->delete();
            foreach ($items as $item) {
                $category = KategoriJasa::findOrFail($item['service_category_id']);
                $servicePurchase->serviceDetails()->create($item + [
                    'id_kategori' => $category->kategori_bahan_id,
                    'service_type' => $category->code,
                    'subtotal' => (float)$item['quantity'] * (float)$item['unit_price'],
                ]);
            }
        });
        return redirect()->route('pesanan-jasa.show', $servicePurchase)->with('success', 'PO jasa diperbarui.');
    }
    public function destroy(PesananJasa $servicePurchase)
    {
        $this->authorize('delete', $servicePurchase);
        $servicePurchase->delete();
        return redirect()->route('pesanan-jasa.index')->with('success', 'PO jasa dihapus.');
    }
    private function formData(): array
    {
        return [
            'suppliers' => Supplier::orderBy('nama')->get(),
            'categories' => KategoriJasa::where('is_active', true)->orderBy('display_code')->get(),
            'asets' => Aset::where('status', Aset::ACTIVE)->orderBy('name')->limit(300)->get(),
        ];
    }
}
