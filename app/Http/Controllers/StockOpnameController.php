<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveStockOpnameRequest;
use App\Http\Requests\StoreStockOpnameRequest;
use App\Http\Requests\UpdateStockOpnameRequest;
use App\Models\Gudang;
use App\Models\StokGudang;
use App\Models\Bahan;
use App\Models\StockOpname;
use App\Services\StockOpnameService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\AccountingPeriodService;
use App\Services\DocumentNumberService;
use App\Exports\StockOpnameInventoryExport;
use Maatwebsite\Excel\Facades\Excel;

class StockOpnameController extends Controller
{
    public function __construct(private StockOpnameService $service, private AccountingPeriodService $periods, private DocumentNumberService $numbers) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', StockOpname::class);
        $warehouseIds = $request->user()->accessibleGudangIds('opname');

        if ($request->ajax()) {
            $query = StockOpname::with('warehouse')
                ->withCount('details')
                ->when($request->user()->isProduction(), fn($builder) => $builder->whereIn('warehouse_id', $warehouseIds))
                ->latest('cutoff_at');
            return datatables()->of($query)
                ->filterColumn('warehouse_name', fn($builder, $keyword) => $builder->whereHas(
                    'warehouse',
                    fn($warehouse) => $warehouse->where('nama', 'like', "%{$keyword}%")
                ))
                ->addColumn('warehouse_name', fn($row) => $row->warehouse->nama ?? '-')
                ->addColumn('can_update', fn($row) => $request->user()->can('update', $row))
                ->addColumn('can_delete', fn($row) => $request->user()->can('delete', $row))
                ->addColumn('can_submit', fn($row) => $request->user()->can('submit', $row))
                ->addColumn('can_approve', fn($row) => $request->user()->can('approve', $row))
                ->addColumn('can_reject', fn($row) => $request->user()->can('reject', $row))
                ->addColumn('can_post', fn($row) => $request->user()->can('post', $row))
                ->make(true);
        }
        return view('stock_opname.index');
    }

    public function create()
    {
        $this->authorize('create', StockOpname::class);
        return view('stock_opname.create', $this->formData() + [
            'documentNumber' => $this->numbers->internal('OPN', 'INV'),
        ]);
    }

    public function store(StoreStockOpnameRequest $request)
    {
        $opname = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $this->periods->assertOpen($data['cutoff_at'], 'Stock opname');
            abort_unless($request->user()->canAccessGudang((int) $data['warehouse_id'], 'opname'), 403);
            $this->ensureNoOpenOpname((int) $data['warehouse_id']);
            $opname = StockOpname::create([
                'number' => $data['number'],
                'warehouse_id' => $data['warehouse_id'],
                'cutoff_at' => $data['cutoff_at'],
                'status' => StockOpname::DRAFT,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);
            $this->replaceDetails($opname, $data['items']);
            return $opname;
        });
        return response()->json(['success' => true, 'message' => 'Draft stock opname berhasil dibuat.', 'data' => $opname], 201);
    }

    public function show(StockOpname $stockOpname)
    {
        $this->authorize('view', $stockOpname);
        return view('stock_opname.show', [
            'opname' => $stockOpname->load('warehouse', 'details.bahan.tipeBarang'),
            'financial' => request()->user()->can('viewFinancials', StockOpname::class),
        ]);
    }

    public function edit(StockOpname $stockOpname)
    {
        $this->authorize('update', $stockOpname);
        return view('stock_opname.edit', array_merge($this->formData(), ['opname' => $stockOpname->load('details')]));
    }

    public function update(UpdateStockOpnameRequest $request, StockOpname $stockOpname)
    {
        DB::transaction(function () use ($request, $stockOpname) {
            $data = $request->validated();
            $this->periods->assertOpen($data['cutoff_at'], 'Stock opname');
            abort_unless($request->user()->canAccessGudang((int) $data['warehouse_id'], 'opname'), 403);
            $stockOpname->update([
                'warehouse_id' => $data['warehouse_id'],
                'cutoff_at' => $data['cutoff_at'],
                'notes' => $data['notes'] ?? null,
                'status' => StockOpname::DRAFT,
                'approval_note' => null
            ]);
            $this->replaceDetails($stockOpname, $data['items']);
        });
        return response()->json(['success' => true, 'message' => 'Draft stock opname berhasil diperbarui.']);
    }

    public function destroy(StockOpname $stockOpname)
    {
        $this->authorize('delete', $stockOpname);
        $stockOpname->delete();
        return response()->json(['success' => true, 'message' => 'Draft stock opname berhasil dihapus.']);
    }

    public function submit(StockOpname $stockOpname)
    {
        $this->authorize('submit', $stockOpname);
        DB::transaction(function () use ($stockOpname) {
            $this->service->confirmPhysical($stockOpname);
            $stockOpname->update(['status' => StockOpname::SUBMITTED, 'submitted_by' => Auth::id(), 'submitted_at' => now()]);
        });
        return response()->json(['success' => true, 'message' => 'Stock opname dikirim untuk approval accounting.']);
    }

    public function approve(ApproveStockOpnameRequest $request, StockOpname $stockOpname)
    {
        DB::transaction(function () use ($request, $stockOpname) {
            $locked = StockOpname::lockForUpdate()->findOrFail($stockOpname->id);
            $this->service->confirmValuation($locked, $request->validated('items', []));
            $locked->update([
                'status' => StockOpname::APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_note' => $request->validated('approval_note')
            ]);
        });
        return response()->json(['success' => true, 'message' => 'Valuasi Accounting dikonfirmasi. Opname siap diposting.']);
    }

    public function reject(ApproveStockOpnameRequest $request, StockOpname $stockOpname)
    {
        $this->authorize('reject', $stockOpname);
        abort_if(trim((string) $request->input('approval_note')) === '', 422, 'Catatan penolakan wajib diisi.');
        $stockOpname->update([
            'status' => StockOpname::REJECTED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_note' => $request->validated('approval_note')
        ]);
        return response()->json(['success' => true, 'message' => 'Stock opname dikembalikan untuk diperbaiki.']);
    }

    public function post(StockOpname $stockOpname)
    {
        $this->authorize('post', $stockOpname);
        $this->service->post($stockOpname);
        return response()->json(['success' => true, 'message' => 'Stock opname berhasil diposting ke stok dan jurnal.']);
    }

    public function reportPdf(StockOpname $stockOpname)
    {
        $this->authorize('view', $stockOpname);
        $stockOpname->load('warehouse', 'details.bahan');
        return Pdf::loadView('stock_opname.pdf', [
            'opname' => $stockOpname,
            'financial' => request()->user()->can('viewFinancials', StockOpname::class),
        ])->setPaper('a4', 'landscape')
            ->stream("stock-opname-{$stockOpname->number}.pdf");
    }

    public function exportInventory(Request $request)
    {
        $this->authorize('viewAny', StockOpname::class);
        $financial = $request->user()->can('viewFinancials', StockOpname::class);
        return Excel::download(
            new StockOpnameInventoryExport($financial),
            'template-stock-opname-' . now()->format('Ymd-His') . '.xlsx'
        );
    }

    public function detailData(StockOpname $stockOpname, Request $request)
    {
        $this->authorize('view', $stockOpname);
        $financial = $request->user()->can('viewFinancials', StockOpname::class);
        $stockOpname->load('warehouse', 'details.bahan');

        return response()->json([
            'number' => $stockOpname->number,
            'warehouse' => $stockOpname->warehouse->nama ?? '-',
            'status' => $stockOpname->status,
            'financial' => $financial,
            'items' => $stockOpname->details->map(function ($detail) use ($financial) {
                $row = [
                    'name' => $detail->bahan->nama ?? '-',
                    'unit' => $detail->bahan->satuan ?? '',
                    'system_quantity' => (float) $detail->system_quantity,
                    'physical_quantity' => (float) $detail->physical_quantity,
                    'difference_quantity' => (float) $detail->difference_quantity,
                    'direction' => (float) $detail->difference_quantity > 0 ? 'PLUS' : ((float) $detail->difference_quantity < 0 ? 'MINUS' : 'MATCH'),
                    'reason' => $detail->reason,
                ];
                if ($financial) {
                    $row['unit_cost'] = (float) $detail->unit_cost;
                    $row['difference_value'] = (float) $detail->difference_value;
                }
                return $row;
            })->values(),
        ]);
    }

    public function reportListPdf(Request $request)
    {
        $this->authorize('viewAny', StockOpname::class);
        $search = trim((string) $request->input('search', ''));
        $filters = collect($request->input('filters', []))->filter(fn($value) => $value !== '');
        $query = StockOpname::with('warehouse')->withCount('details')->latest('cutoff_at');
        if ($request->user()->isProduction()) {
            $query->whereIn('warehouse_id', $request->user()->accessibleGudangIds('opname'));
        }
        if ($search !== '') {
            $query->where(fn($q) => $q->where('number', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhereHas('warehouse', fn($warehouse) => $warehouse->where('nama', 'like', "%{$search}%")));
        }
        foreach (['number', 'cutoff_at', 'status'] as $field) {
            if ($filters->has($field)) $query->where($field, 'like', "%{$filters[$field]}%");
        }
        if ($filters->has('warehouse_name')) {
            $query->whereHas('warehouse', fn($warehouse) => $warehouse->where('nama', 'like', "%{$filters['warehouse_name']}%"));
        }
        $rows = $query->limit(5000)->get()->map(fn($row) => [
            'number' => $row->number,
            'cutoff_at' => $row->cutoff_at->format('d-m-Y H:i'),
            'warehouse' => $row->warehouse->nama ?? '-',
            'items' => $row->details_count,
            'status' => $row->status,
        ]);
        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Daftar Stock Opname',
            'columns' => [
                ['key' => 'number', 'label' => 'Nomor', 'align' => 'left'],
                ['key' => 'cutoff_at', 'label' => 'Cut-off', 'align' => 'left'],
                ['key' => 'warehouse', 'label' => 'Gudang', 'align' => 'left'],
                ['key' => 'items', 'label' => 'Jumlah Item', 'align' => 'right'],
                ['key' => 'status', 'label' => 'Status', 'align' => 'left'],
            ],
            'rows' => $rows,
            'search' => $search,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream('daftar-stock-opname.pdf');
    }

    private function replaceDetails(StockOpname $opname, array $items): void
    {
        $opname->details()->delete();
        foreach ($items as $item) {
            $bahan = Bahan::whereKey($item['bahan_id'])->firstOrFail();
            $saldo = StokGudang::where('gudang_id', $opname->warehouse_id)->where('bahan_id', $bahan->id)->firstOrFail();
            $opname->details()->create([
                'bahan_id' => $bahan->id,
                'system_quantity' => $saldo->stok_tersedia,
                'physical_quantity' => $item['physical_quantity'],
                'difference_quantity' => (float) $item['physical_quantity'] - (float) $saldo->stok_tersedia,
                'physical_confirmed_by' => null,
                'physical_confirmed_at' => null,
                'valuation_confirmed_by' => null,
                'valuation_confirmed_at' => null,
                'reason' => $item['reason'] ?? null,
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    private function ensureNoOpenOpname(int $warehouseId): void
    {
        abort_if(StockOpname::where('warehouse_id', $warehouseId)
            ->whereIn('status', [StockOpname::DRAFT, StockOpname::SUBMITTED, StockOpname::APPROVED, StockOpname::REJECTED])
            ->exists(), 422, 'Masih ada stock opname gudang ini yang belum selesai.');
    }

    private function formData(): array
    {
        $user = request()->user();
        $warehouseIds = $user->accessibleGudangIds('opname');

        return [
            'warehouses' => Gudang::whereIn('id', $warehouseIds)->orderBy('nama')->get(),
            'stocks' => StokGudang::with('bahan.tipeBarang')
                ->whereIn('gudang_id', $warehouseIds)
                ->where('stok_tersedia', '>', 0)
                ->orderBy('gudang_id')
                ->orderBy('bahan_id')
                ->get(),
        ];
    }
}
