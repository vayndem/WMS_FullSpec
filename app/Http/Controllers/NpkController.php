<?php

namespace App\Http\Controllers;

use App\Models\Npk;
use App\Models\Bahan;
use App\Models\Gudang;
use App\Models\Jurnal;
use App\Http\Requests\StoreNpkRequest;
use App\Http\Requests\UpdateNpkRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\WmsAccountingService;
use App\Services\AccountingPeriodService;
use App\Services\DocumentNumberService;
use App\Services\StokGudangService;
use App\Services\WarehouseExecutionService;
use App\Models\InventoryReservation;

class NpkController extends Controller
{
    public function __construct(private WmsAccountingService $accounting, private AccountingPeriodService $periods, private DocumentNumberService $numbers, private StokGudangService $stokGudang, private WarehouseExecutionService $execution) {}
    public function index(Request $request)
    {
        $this->authorize('viewAny', Npk::class);
        $financial = $request->user()->can('viewFinancials', Npk::class);
        $warehouseIds = $request->user()->accessibleGudangIds('npk');

        if ($request->ajax()) {
            $query = Npk::with(['barang', 'gudangAsal', 'gudangTujuan'])
                ->when($request->user()->isProduction(), fn($builder) => $builder->whereIn('id_gudang_asal', $warehouseIds))
                ->when($request->filled('status'), function ($query) use ($request) {
                    $query->where('status', $request->input('status'));
                });

            if (!$financial) {
                $query->select([
                    'id',
                    'kode',
                    'kode_datapesanan',
                    'tanggal',
                    'id_barang',
                    'jumlah',
                    'jumlah_stok',
                    'satuan_transaksi',
                    'status',
                    'operator',
                    'id_gudang_asal',
                    'id_gudang_tujuan',
                ]);
            }

            return datatables()->of($query)
                ->filterColumn('barang.nama', function ($query, $keyword) {
                    $query->whereHas('barang', function ($barang) use ($keyword) {
                        $barang->where('nama', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('nama_barang', function ($row) {
                    return $row->barang->nama ?? '-';
                })
                ->addColumn('jumlah_display', function ($row) {
                    $unit = $row->satuan_transaksi ?: ($row->barang->satuan ?? '');
                    return number_format((float) $row->jumlah, 2, ',', '.') . ' ' . $unit;
                })
                ->addColumn('can_update', function ($row) use ($request) {
                    return $request->user()->can('update', $row);
                })
                ->addColumn('can_delete', function ($row) use ($request) {
                    return $request->user()->can('delete', $row);
                })
                ->make(true);
        }

        $bahans = Bahan::with(['kategoriBahan', 'stokGudangs'])->orderBy('nama', 'asc')->get();
        $gudangs = $this->availableWarehouses($request->user(), 'npk');

        return view('npk.index', compact('bahans', 'gudangs', 'financial'));
    }

    public function reportPdf(Request $request)
    {
        $this->authorize('viewAny', Npk::class);
        $financial = $request->user()->can('viewFinancials', Npk::class);

        $filters = collect($request->input('filters', []))->filter(fn($value) => $value !== '');
        $search = trim((string) $request->input('search', ''));
        $query = Npk::with('barang')
            ->when($request->user()->isProduction(), fn ($q) => $q->whereIn('id_gudang_asal', $request->user()->accessibleGudangIds('npk')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->latest('tanggal');

        if ($search !== '') {
            $query->where(fn($q) => $q->where('kode', 'like', "%{$search}%")
                ->orWhere('kode_datapesanan', 'like', "%{$search}%")
                ->orWhere('tanggal', 'like', "%{$search}%")
                ->orWhere('operator', 'like', "%{$search}%")
                ->orWhereHas('barang', fn($barang) => $barang->where('nama', 'like', "%{$search}%")));
        }

        foreach (['kode', 'kode_datapesanan', 'tanggal', 'jumlah', 'status', 'operator'] as $field) {
            if ($filters->has($field)) {
                $query->where($field, 'like', "%{$filters[$field]}%");
            }
        }
        if ($filters->has('nama_barang')) {
            $query->whereHas('barang', fn($barang) => $barang->where('nama', 'like', "%{$filters['nama_barang']}%"));
        }

        $rows = $query->limit(5000)->get()->map(function ($row) use ($financial) {
            $data = [
                'kode' => $row->kode,
                'kode_datapesanan' => $row->kode_datapesanan ?: '-',
                'tanggal' => $row->tanggal,
                'nama_barang' => $row->barang->nama ?? '-',
                'jumlah' => number_format($row->jumlah, 2, ',', '.') . ' ' .
                    ($row->satuan_transaksi ?: ($row->barang->satuan ?? '')),
                'status' => $row->status === Npk::POSTED ? 'Keluar' : ($row->status === Npk::REVERSED ? 'Reversed' : 'Draft'),
                'operator' => $row->operator ?: '-',
            ];
            if ($financial) {
                $data['harga_satuan'] = 'Rp ' . number_format($row->harga_satuan, 2, ',', '.');
                $data['total_nilai'] = 'Rp ' . number_format($row->total_nilai, 2, ',', '.');
            }
            return $data;
        });

        $columns = [
            ['key' => 'kode', 'label' => 'Kode NPK', 'align' => 'left'],
            ['key' => 'kode_datapesanan', 'label' => 'Kode Pesanan', 'align' => 'left'],
            ['key' => 'tanggal', 'label' => 'Tanggal', 'align' => 'left'],
            ['key' => 'nama_barang', 'label' => 'Nama Barang', 'align' => 'left'],
            ['key' => 'jumlah', 'label' => 'Jumlah', 'align' => 'right'],
        ];
        if ($financial) {
            $columns[] = ['key' => 'harga_satuan', 'label' => 'Harga Rata-rata', 'align' => 'right'];
            $columns[] = ['key' => 'total_nilai', 'label' => 'Nilai Pemakaian', 'align' => 'right'];
        }
        $columns[] = ['key' => 'status', 'label' => 'Status', 'align' => 'left'];
        $columns[] = ['key' => 'operator', 'label' => 'Operator', 'align' => 'left'];

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Daftar NPK',
            'columns' => $columns,
            'rows' => $rows,
            'search' => $search,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream('daftar-npk.pdf');
    }

    public function create()
    {
        $this->authorize('create', Npk::class);

        $bahans = Bahan::with('stokGudangs')->orderBy('nama', 'asc')->get();
        $gudangs = $this->availableWarehouses(request()->user(), 'npk');
        $documentNumber = $this->numbers->external('NPK');
        $reservations = InventoryReservation::with(['bahan', 'gudang'])->whereIn('gudang_id', request()->user()->accessibleGudangIds('npk'))->whereIn('status', ['ACTIVE', 'PICKED'])->get();

        return view('npk.create', compact('bahans', 'gudangs', 'documentNumber', 'reservations'));
    }

    public function store(StoreNpkRequest $request)
    {
        $validated = $request->validated();
        $this->periods->assertOpen($validated['tanggal'], 'NPK');
        abort_unless($request->user()->canAccessGudang((int) $validated['id_gudang_asal'], 'npk'), 403);

        $npk = DB::transaction(function () use ($validated, $request) {
            $isKeluar = $validated['status'] === Npk::POSTED;
            $bahan = Bahan::lockForUpdate()->findOrFail($validated['id_barang']);

            $validated['id_user'] = $request->user()->id ?? 0;
            $validated['jumlah_terkirim'] = $isKeluar ? $validated['jumlah'] : 0;
            $validated['tgl_terkirim'] = $isKeluar ? ($validated['tanggal'] ?? now()) : null;
            $validated['jumlah_stok'] = $bahan->toStockQuantity((float) $validated['jumlah']);
            $validated['satuan_transaksi'] = $bahan->hasSmallUnit()
                ? $bahan->satuan_kecil
                : $bahan->satuan;

            $npk = Npk::create($validated);

            if ($isKeluar) {
                if ($npk->inventory_reservation_id) $this->execution->consumeReservation(InventoryReservation::findOrFail($npk->inventory_reservation_id), (int) $npk->id_gudang_asal, (int) $npk->id_barang, (float) $npk->jumlah_stok);
                $this->stokGudang->saldo((int) $npk->id_gudang_asal, (int) $npk->id_barang);
                $this->accounting->consumeStock($npk);
                $this->stokGudang->keluar((int) $npk->id_gudang_asal, (int) $npk->id_barang, (float) $npk->jumlah_stok, (float) $npk->harga_satuan, 'PENGELUARAN', 'NPK', $npk->id, $npk->kode);
                $this->accounting->postNpk($npk->fresh());
            }

            return $npk;
        });

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran barang (NPK) berhasil disimpan.',
            'data'    => $npk
        ], 201);
    }

    public function show($id)
    {
        $npk = Npk::with(['barang', 'gudangAsal', 'gudangTujuan'])->findOrFail($id);
        $this->authorize('view', $npk);
        $npk->setAttribute(
            'jumlah_display',
            number_format((float) $npk->jumlah, 2, ',', '.') . ' ' .
                ($npk->satuan_transaksi ?: ($npk->barang->satuan ?? ''))
        );
        if (!request()->user()->can('viewFinancials', Npk::class)) {
            $npk->makeHidden(['harga_satuan', 'total_nilai']);
        }

        return response()->json([
            'success' => true,
            'data'    => $npk
        ]);
    }

    public function edit($id)
    {
        $npk = Npk::with(['barang', 'gudangAsal', 'gudangTujuan'])->findOrFail($id);
        $this->authorize('update', $npk);

        if ($npk->barang?->hasSmallUnit() && $npk->satuan_transaksi !== $npk->barang->satuan_kecil) {
            $npk->jumlah = $npk->barang->smallUnitEquivalent(
                (float) ($npk->jumlah_stok ?: $npk->jumlah)
            );
        }

        $bahans = Bahan::with('stokGudangs')->orderBy('nama', 'asc')->get();
        $gudangs = $this->availableWarehouses(request()->user(), 'npk');
        $reservations = InventoryReservation::with(['bahan', 'gudang'])->whereIn('gudang_id', request()->user()->accessibleGudangIds('npk'))->whereIn('status', ['ACTIVE', 'PICKED'])->get();

        return view('npk.edit', compact('npk', 'bahans', 'gudangs', 'reservations'));
    }

    public function update(UpdateNpkRequest $request, $id)
    {
        $npk = Npk::findOrFail($id);
        $this->authorize('update', $npk);

        $validated = $request->validated();
        $this->periods->assertOpen($validated['tanggal'], 'NPK');
        abort_unless($request->user()->canAccessGudang((int) $validated['id_gudang_asal'], 'npk'), 403);

        DB::transaction(function () use ($npk, $validated) {
            $willClose = $validated['status'] === Npk::POSTED;
            $bahan = Bahan::lockForUpdate()->findOrFail($validated['id_barang']);

            $validated['jumlah_terkirim'] = $willClose ? ($validated['jumlah_terkirim'] ?? $validated['jumlah']) : 0;
            $validated['tgl_terkirim'] = $willClose ? ($validated['tgl_terkirim'] ?? $npk->tanggal) : null;
            $validated['jumlah_stok'] = $bahan->toStockQuantity((float) $validated['jumlah']);
            $validated['satuan_transaksi'] = $bahan->hasSmallUnit()
                ? $bahan->satuan_kecil
                : $bahan->satuan;

            $npk->update($validated);

            if ($willClose) {
                if ($npk->inventory_reservation_id) $this->execution->consumeReservation(InventoryReservation::findOrFail($npk->inventory_reservation_id), (int) $npk->id_gudang_asal, (int) $npk->id_barang, (float) $npk->jumlah_stok);
                $this->stokGudang->saldo((int) $npk->id_gudang_asal, (int) $npk->id_barang);
                $this->accounting->consumeStock($npk);
                $this->stokGudang->keluar((int) $npk->id_gudang_asal, (int) $npk->id_barang, (float) $npk->jumlah_stok, (float) $npk->harga_satuan, 'PENGELUARAN', 'NPK', $npk->id, $npk->kode);
                $this->accounting->postNpk($npk->fresh());
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran barang (NPK) berhasil diperbarui.',
            'data'    => $npk
        ]);
    }

    public function destroy($id)
    {
        $npk = Npk::findOrFail($id);
        $this->authorize('delete', $npk);

        DB::transaction(function () use ($npk) {
            $npk->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran barang (NPK) berhasil dihapus.'
        ]);
    }

    private function syncJurnalPengeluaranBarang(Npk $npk, ?string $oldKode = null): void
    {
        $this->accounting->postNpk($npk);
    }

    private function availableWarehouses($user, string $ability)
    {
        return Gudang::whereIn('id', $user->accessibleGudangIds($ability))
            ->where('jenis', Gudang::NORMAL)
            ->orderBy('nama')
            ->get();
    }
}
