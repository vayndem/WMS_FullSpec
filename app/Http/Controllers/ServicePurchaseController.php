<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServicePurchaseRequest;
use App\Models\ServicePurchase;
use App\Models\ServiceCategory;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\DocumentNumberService;

class ServicePurchaseController extends Controller
{
    public function __construct(private DocumentNumberService $numbers) {}
    public function index(Request $request)
    {
        $this->authorize('viewAny', ServicePurchase::class);
        $query = ServicePurchase::with('supplier')->withSum('serviceDetails', 'subtotal')
            ->when($request->filled('q'), fn($q) => $q->where(fn($x) => $x->where('no_po', 'like', '%' . $request->q . '%')->orWhereHas('supplier', fn($s) => $s->where('nama', 'like', '%' . $request->q . '%'))))
            ->latest('tanggal');
        $perPage = $this->perPage($request, $query->count());
        $orders = $query->paginate($perPage)->withQueryString();
        return view('service_purchases.index', compact('orders'));
    }
    private function perPage(Request $request, int $total): int
    {
        $value = strtolower((string)$request->input('per_page', 10));
        return $value === 'all' ? max(1, $total) : (in_array((int)$value, [10, 20, 50, 100], true) ? (int)$value : 10);
    }
    public function reportPdf(Request $request)
    {
        $this->authorize('viewAny', ServicePurchase::class);
        $rows = ServicePurchase::with('supplier')->withSum('serviceDetails', 'subtotal')->when($request->filled('q'), fn($q) => $q->where('no_po', 'like', '%' . $request->q . '%'))->get()->map(fn($po) => ['number' => $po->no_po, 'date' => $po->tanggal, 'supplier' => $po->supplier->nama, 'amount' => 'Rp ' . number_format($po->service_details_sum_subtotal, 0, ',', '.')]);
        return Pdf::loadView('reports.table-pdf', ['title' => 'Daftar PO Jasa', 'columns' => [['key' => 'number', 'label' => 'No PO'], ['key' => 'date', 'label' => 'Tanggal'], ['key' => 'supplier', 'label' => 'Supplier'], ['key' => 'amount', 'label' => 'Nilai', 'align' => 'right']], 'rows' => $rows, 'search' => $request->q, 'filters' => collect(), 'generatedAt' => now()])->setPaper('a4', 'landscape')->stream('po-jasa.pdf');
    }
    public function create()
    {
        $this->authorize('create', ServicePurchase::class);
        return view('service_purchases.form', $this->formData() + ['documentNumber' => $this->numbers->financial('PJ')]);
    }
    public function store(StoreServicePurchaseRequest $request)
    {
        $po = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $items = $data['items'];
            unset($data['items']);
            $subtotal = collect($items)->sum(fn($x) => (float)$x['quantity'] * (float)$x['unit_price']);
            $po = ServicePurchase::create($data + ['document_type' => 'SERVICE', 'total_exclude' => $subtotal, 'total_include' => $subtotal, 'grand_total' => $subtotal, 'no_order' => '-', 'status' => ServicePurchase::OPEN]);
            foreach ($items as $item) {
                $category = ServiceCategory::findOrFail($item['service_category_id']);
                $po->serviceDetails()->create($item + [
                    'id_kategori' => $category->kategori_bahan_id,
                    'service_type' => $category->code,
                    'subtotal' => (float)$item['quantity'] * (float)$item['unit_price'],
                ]);
            }
            return $po;
        });
        return redirect()->route('service-purchases.show', $po)->with('success', 'PO jasa berhasil dibuat.');
    }
    public function show(ServicePurchase $servicePurchase)
    {
        $this->authorize('view', $servicePurchase);
        $servicePurchase->load(['supplier', 'serviceDetails.category', 'serviceDetails.kategori', 'serviceDetails.bapDetails.lpb']);
        return view('service_purchases.show', [
            'po' => $servicePurchase,
            'financial' => request()->user()->can('viewFinancials', $servicePurchase),
        ]);
    }
    public function edit(ServicePurchase $servicePurchase)
    {
        $this->authorize('update', $servicePurchase);
        return view('service_purchases.form', $this->formData() + ['po' => $servicePurchase->load('serviceDetails')]);
    }
    public function update(StoreServicePurchaseRequest $request, ServicePurchase $servicePurchase)
    {
        DB::transaction(function () use ($request, $servicePurchase) {
            $data = $request->validated();
            $items = $data['items'];
            unset($data['items']);
            $subtotal = collect($items)->sum(fn($x) => (float)$x['quantity'] * (float)$x['unit_price']);
            $servicePurchase->update($data + ['total_exclude' => $subtotal, 'total_include' => $subtotal, 'grand_total' => $subtotal]);
            $servicePurchase->serviceDetails()->delete();
            foreach ($items as $item) {
                $category = ServiceCategory::findOrFail($item['service_category_id']);
                $servicePurchase->serviceDetails()->create($item + [
                    'id_kategori' => $category->kategori_bahan_id,
                    'service_type' => $category->code,
                    'subtotal' => (float)$item['quantity'] * (float)$item['unit_price'],
                ]);
            }
        });
        return redirect()->route('service-purchases.show', $servicePurchase)->with('success', 'PO jasa diperbarui.');
    }
    public function destroy(ServicePurchase $servicePurchase)
    {
        $this->authorize('delete', $servicePurchase);
        $servicePurchase->delete();
        return redirect()->route('service-purchases.index')->with('success', 'PO jasa dihapus.');
    }
    private function formData(): array
    {
        return ['suppliers' => Supplier::orderBy('nama')->get(), 'categories' => ServiceCategory::where('is_active', true)->orderBy('display_code')->get()];
    }
}
