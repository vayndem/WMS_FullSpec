<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\StoreAssetDepreciationRequest;
use App\Http\Requests\StoreAssetDisposalRequest;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\ChartOfAccount;
use App\Services\AssetAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\DocumentNumberService;

class AssetController extends Controller
{
    public function __construct(private AssetAccountingService $accounting, private DocumentNumberService $numbers) {}
    public function index(Request $request)
    {
        $this->authorize('viewAny', Asset::class);
        $financial = $request->user()->can('viewFinancials', Asset::class);
        $statusFilter = (string) $request->input('status', 'ACTIVE');
        $query = Asset::with('category')->when($request->filled('q'), fn($q) => $q->where(fn($x) => $x->where('asset_number', 'like', '%' . $request->q . '%')->orWhere('name', 'like', '%' . $request->q . '%')->orWhere('location', 'like', '%' . $request->q . '%')))
            ->when($statusFilter !== '', fn($q) => $q->where('status', $statusFilter))->latest();
        $perPage = $this->perPage($request, $query->count());
        $assets = $query->paginate($perPage)->withQueryString();
        return view('assets.index', compact('assets', 'financial'));
    }

    private function perPage(Request $request, int $total): int
    {
        $value = strtolower((string) $request->input('per_page', 10));
        return $value === 'all' ? max(1, $total) : (in_array((int) $value, [10, 20, 50, 100], true) ? (int) $value : 10);
    }
    public function reportPdf(Request $request)
    {
        $this->authorize('viewAny', Asset::class);
        $financial = $request->user()->can('viewFinancials', Asset::class);
        $rows = Asset::with('category')->when($request->filled('q'), fn($q) => $q->where(fn($x) => $x->where('asset_number', 'like', '%' . $request->q . '%')->orWhere('name', 'like', '%' . $request->q . '%')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))->get()->map(function ($a) use ($financial) {
                $row = ['number' => $a->asset_number, 'name' => $a->name, 'category' => $a->category->name, 'location' => $a->location ?: '-', 'status' => $a->status];
                if ($financial) {
                    $row['cost'] = 'Rp ' . number_format($a->acquisition_cost, 0, ',', '.');
                    $row['book'] = 'Rp ' . number_format($a->book_value, 0, ',', '.');
                }
                return $row;
            });
        $columns = [['key' => 'number', 'label' => 'No Asset'], ['key' => 'name', 'label' => 'Nama'], ['key' => 'category', 'label' => 'Kategori'], ['key' => 'location', 'label' => 'Lokasi']];
        if ($financial) {
            $columns[] = ['key' => 'cost', 'label' => 'Harga Perolehan', 'align' => 'right'];
            $columns[] = ['key' => 'book', 'label' => 'Nilai Buku', 'align' => 'right'];
        }
        $columns[] = ['key' => 'status', 'label' => 'Status'];
        return Pdf::loadView('reports.table-pdf', compact('columns', 'rows') + ['title' => 'Daftar Asset Tetap', 'search' => $request->q, 'filters' => collect(['status' => $request->status]), 'generatedAt' => now()])->setPaper('a4', 'landscape')->stream('asset-tetap.pdf');
    }
    public function create()
    {
        $this->authorize('create', Asset::class);
        return view('assets.form', $this->formData() + [
            'documentNumber' => $this->numbers->financial('AS'),
        ]);
    }
    public function store(StoreAssetRequest $request)
    {
        $data = $request->validated();
        $asset = DB::transaction(function () use ($data) {
            $cost = (float)$data['acquisition_cost'];
            $opening = (float)($data['opening_accumulated_depreciation'] ?? 0);
            $asset = Asset::create($data + [
                'accumulated_depreciation' => $opening,
                'book_value' => $cost - $opening,
                'created_by' => Auth::id(),
                'status' => 'ACTIVE',
            ]);
            $this->accounting->postAcquisition($asset);
            return $asset;
        });
        return redirect()->route('assets.show', $asset)->with('success', 'Asset dan jurnal perolehan berhasil dibuat.');
    }
    public function show(Request $request, Asset $asset)
    {
        $this->authorize('view', $asset);
        $asset->load(['category', 'acquisitionCreditAccount', 'depreciations.journal', 'disposal.journal']);
        $financial = $request->user()->can('viewFinancials', Asset::class);
        $cashBanks = ChartOfAccount::where('is_active', true)->where('is_cash_bank', true)->orderBy('kode_akun')->get();
        return view('assets.show', compact('asset', 'financial', 'cashBanks'));
    }
    public function edit(Asset $asset)
    {
        $this->authorize('update', $asset);
        return view('assets.form', $this->formData($asset) + compact('asset'));
    }
    public function update(StoreAssetRequest $request, Asset $asset)
    {
        if ($asset->depreciations()->exists()) abort(422, 'Asset yang sudah disusutkan tidak dapat mengubah data perolehan.');
        DB::transaction(function () use ($request, $asset) {
            $data = $request->validated();
            $cost = (float)$data['acquisition_cost'];
            $opening = (float)($data['opening_accumulated_depreciation'] ?? 0);
            $asset->update($data + ['accumulated_depreciation' => $opening, 'book_value' => $cost - $opening]);
            $this->accounting->postAcquisition($asset->fresh());
        });
        return redirect()->route('assets.show', $asset)->with('success', 'Asset dan jurnal perolehan diperbarui.');
    }
    public function depreciate(StoreAssetDepreciationRequest $request, Asset $asset)
    {
        $this->accounting->depreciate($asset, $request->validated());
        return back()->with('success', 'Penyusutan berhasil diposting.');
    }
    public function dispose(StoreAssetDisposalRequest $request, Asset $asset)
    {
        $this->accounting->dispose($asset, $request->validated());
        return back()->with('success', 'Pelepasan asset berhasil diposting.');
    }
    private function formData(?Asset $asset = null): array
    {
        return [
            'categories' => AssetCategory::where('is_active', true)->orderBy('name')->get(),
            'accounts' => ChartOfAccount::where('is_active', true)->where('is_postable', true)->orderBy('kode_akun')->get()
        ];
    }
}
