<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\Gudang;
use App\Models\InventoryLayer;
use App\Models\KategoriBahan;
use App\Models\StokGudang;
use App\Http\Requests\UpdateBahanRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class BahanController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Bahan::class);

        if ($request->ajax()) {
            $kategoriId = $request->input('kategori_id');
            $gudangId = $request->input('gudang_id');
            $gudangNama = $gudangId
                ? (Gudang::query()->whereKey($gudangId)->value('nama') ?? '-')
                : 'Seluruh gudang';

            $financial = $request->user()->can('viewFinancials', Bahan::class);
            $layerSummary = InventoryLayer::query()
                ->select('bahan_id')
                ->selectRaw('COUNT(*) as layer_count')
                ->selectRaw('SUM(remaining_quantity) as layer_quantity')
                ->when($gudangId, fn ($query) => $query->where('gudang_id', $gudangId))
                ->groupBy('bahan_id');
            $stockSummary = StokGudang::query()->select('bahan_id')
                ->selectRaw('SUM(stok_tersedia) as stok_tersedia')
                ->selectRaw('SUM(stok_dipesan) as stok_dipesan')
                ->when($gudangId, fn ($query) => $query->where('gudang_id', $gudangId))
                ->groupBy('bahan_id');
            if ($financial) {
                $layerSummary
                    ->selectRaw('SUM(remaining_quantity * unit_cost) as inventory_value')
                    ->selectRaw('CASE WHEN SUM(remaining_quantity) > 0 THEN SUM(remaining_quantity * unit_cost) / SUM(remaining_quantity) ELSE 0 END as average_cost');
            }

            $query = Bahan::with(['kategoriBahan', 'tipeBarang'])
                ->leftJoinSub($layerSummary, 'layer_summary', 'layer_summary.bahan_id', '=', 'bahan.id')
                ->leftJoinSub($stockSummary, 'stock_summary', 'stock_summary.bahan_id', '=', 'bahan.id')
                ->select('bahan.*')
                ->addSelect([
                    DB::raw('COALESCE(stock_summary.stok_tersedia, 0) as stok_onhand'),
                    DB::raw('COALESCE(stock_summary.stok_dipesan, 0) as stok_onpurchase'),
                    DB::raw('COALESCE(layer_summary.layer_count, 0) as layer_count'),
                    DB::raw('COALESCE(layer_summary.layer_quantity, 0) as layer_quantity'),
                ])
                ->when(!empty($kategoriId), function ($q) use ($kategoriId) {
                    return $q->where('kategori', $kategoriId);
                })
                ->when(!empty($gudangId), fn ($q) => $q->whereNotNull('stock_summary.bahan_id'));

            if ($financial) {
                $query->addSelect([
                    DB::raw('COALESCE(layer_summary.average_cost, 0) as average_cost'),
                    DB::raw('COALESCE(layer_summary.inventory_value, 0) as inventory_value'),
                ]);
            }

            $dataTable = datatables()->of($query)
                ->addColumn('kategori_nama', function ($row) {
                    return $row->kategoriBahan->katnama ?? '-';
                })
                ->addColumn('gudang_nama', fn () => $gudangNama)
                ->addColumn('tipe_barang_nama', function ($row) {
                    return $row->tipeBarang->katnama ?? '-';
                })
                ->addColumn(
                    'stock_status',
                    fn($row) =>
                    abs((float) $row->stok_onhand - (float) $row->layer_quantity) <= 0.000001 ? 'VALID' : 'SELISIH'
                )
                ->addColumn('can_update', fn($row) => $request->user()->can('update', $row));

            return $dataTable->make(true);
        }

        $kategoris = KategoriBahan::orderBy('katnama')->get();
        $gudangs = Gudang::where('aktif', true)->orderBy('nama')->get();

        $financial = $request->user()->can('viewFinancials', Bahan::class);

        return view('bahan.index', compact('kategoris', 'gudangs', 'financial'));
    }

    public function show(Bahan $bahan)
    {
        $this->authorize('view', $bahan);

        $bahan->load(['kategoriBahan', 'gudang', 'tipeBarang', 'stokGudangs.gudang']);
        $financial = request()->user()->can('viewFinancials', Bahan::class);
        $layers = InventoryLayer::query()->with('gudang')->where('bahan_id', $bahan->id)
            ->orderByDesc('transaction_date')->orderByDesc('id')
            ->get($financial
                ? ['id', 'gudang_id', 'source_type', 'source_id', 'transaction_date', 'initial_quantity', 'remaining_quantity', 'unit_cost']
                : ['id', 'gudang_id', 'source_type', 'source_id', 'transaction_date', 'initial_quantity', 'remaining_quantity']);

        return view('bahan.show', compact('bahan', 'layers', 'financial'));
    }

    public function edit(Bahan $bahan)
    {
        $this->authorize('update', $bahan);

        return view('bahan.edit', [
            'bahan' => $bahan,
            'kategoris' => KategoriBahan::orderBy('katnama')->get(),
            'gudangs' => Gudang::where('aktif', true)->orderBy('nama')->get(),
        ]);
    }

    public function update(UpdateBahanRequest $request, Bahan $bahan)
    {
        $data = $request->validated();
        // Pertahankan kategori asli. Re-klasifikasi wajib memakai workflow
        // accounting tersendiri, bukan edit master bahan.
        $data['kategori'] = $bahan->kategori;
        $data['tipe_barang'] = $bahan->tipe_barang;
        $data['berat_kecil'] = $request->filled('satuan_kecil')
            ? (float) $data['berat_kecil']
            : 1;
        $data['satuan_kecil'] = $request->filled('satuan_kecil')
            ? trim((string) $data['satuan_kecil'])
            : null;

        $bahan->update($data);

        return redirect()->route('bahan.show', $bahan)
            ->with('success', 'Master bahan dan konversi satuan berhasil diperbarui.');
    }
}
