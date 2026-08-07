<?php

namespace App\Http\Controllers;

use App\Models\Lpb;
use App\Models\LpbDetail;
use App\Models\Pembelian;
use App\Models\Pembeliandetail;
use App\Models\Bahan;
use App\Models\Jurnal;
use App\Models\KategoriBahan;
use App\Http\Requests\StoreLpbRequest;
use App\Http\Requests\UpdateLpbRequest;
use App\Http\Requests\StoreLpbDetailRequest;
use App\Http\Requests\UpdateLpbDetailRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\WmsAccountingService;
use App\Models\InventoryLayer;
use App\Services\DocumentNumberService;
use App\Services\StokGudangService;

class LpbController extends Controller
{
    public function __construct(private WmsAccountingService $accounting, private DocumentNumberService $numbers, private StokGudangService $stokGudang) {}
    public function index(Request $request)
    {
        $this->authorize('viewAny', Lpb::class);

        if ($request->ajax()) {
            $query = Lpb::with([
                'pembelian.supplier',
                'gudang',
                'user',
                'details.bahan',
                'details.kategori',
                'serviceDetails.servicePoDetail.category',
                'serviceDetails.kategori',
                'serviceDetails.allocations',
            ]);

            $receiptType = strtoupper((string) $request->input('jenis_lpb', ''));
            if (in_array($receiptType, ['3', 'SERVICE_BAP'], true)) {
                $query->where(fn($builder) => $builder
                    ->where('jenis_lpb', 3)
                    ->orWhere('document_type', 'SERVICE_BAP'));
            } elseif (in_array($receiptType, ['1', 'GOODS'], true)) {
                $query->where(fn($builder) => $builder
                    ->where('jenis_lpb', 1)
                    ->orWhere('document_type', 'GOODS')
                    ->orWhereNull('document_type'));
            }

            $table = datatables()->of($query)
                ->filterColumn('supplier_nama', function ($query, $keyword) {
                    $query->whereHas('pembelian.supplier', function ($supplier) use ($keyword) {
                        $supplier->where('nama', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('user_nama', function ($query, $keyword) {
                    $query->whereHas('user', function ($user) use ($keyword) {
                        $user->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('gudang_nama', function ($query, $keyword) {
                    $query->whereHas('gudang', fn($gudang) => $gudang->where('nama', 'like', "%{$keyword}%"));
                })
                ->addIndexColumn()
                ->addColumn('supplier_nama', function ($row) {
                    return $row->pembelian->supplier->nama ?? '-';
                })
                ->addColumn('gudang_nama', fn($row) => $row->gudang->nama ?? '-')
                ->addColumn('user_nama', function ($row) {
                    return $row->user->name ?? 'User #' . $row->id_user;
                })
                ->addColumn('jenis_lpb_label', fn($row) =>
                $row->document_type === 'SERVICE_BAP' ? 'BAP Jasa' : 'LPB Barang')
                ->addColumn('can_update', function ($row) use ($request) {
                    return $request->user()->can('update', $row);
                })
                ->addColumn('can_delete', function ($row) use ($request) {
                    return $request->user()->can('delete', $row);
                });

            if (!in_array((int) $request->user()->type, [5, 33], true)) {
                $table->editColumn('pembelian', fn($row) => [
                    'no_po' => $row->no_po,
                    'supplier' => ['nama' => $row->pembelian->supplier->nama ?? '-'],
                ]);
                $table->editColumn('details', fn($row) => $row->details->map(fn($detail) => [
                    'id' => $detail->id,
                    'id_lpb' => $detail->id_lpb,
                    'jumlah_barang_diterima' => $detail->jumlah_barang_diterima,
                    'lot_number' => $detail->lot_number,
                    'bahan' => $detail->bahan ? [
                        'id' => $detail->bahan->id,
                        'nama' => $detail->bahan->nama,
                        'satuan' => $detail->bahan->satuan,
                    ] : null,
                    'kategori' => $detail->kategori ? [
                        'id' => $detail->kategori->id,
                        'katnama' => $detail->kategori->katnama,
                    ] : null,
                ]));
                $table->editColumn('service_details', fn($row) => $row->serviceDetails->map(fn($detail) => [
                    'id' => $detail->id,
                    'progress_percent' => $detail->progress_percent,
                    'department_cost_center' => $detail->department_cost_center,
                    'notes' => $detail->notes,
                    'service_po_detail' => $detail->servicePoDetail ? [
                        'id' => $detail->servicePoDetail->id,
                        'description' => $detail->servicePoDetail->description,
                        'unit' => $detail->servicePoDetail->unit,
                        'category' => $detail->servicePoDetail->category ? [
                            'display_code' => $detail->servicePoDetail->category->display_code,
                            'name' => $detail->servicePoDetail->category->name,
                        ] : null,
                    ] : null,
                    'kategori' => $detail->kategori ? [
                        'id' => $detail->kategori->id,
                        'katnama' => $detail->kategori->katnama,
                    ] : null,
                    'allocations' => $detail->allocations->map(fn($allocation) => [
                        'datapesanan_code' => $allocation->datapesanan_code,
                        'percentage' => $allocation->percentage,
                    ])->values(),
                ]));
            }

            return $table->make(true);
        }

        $financial = in_array((int) $request->user()->type, [5, 33], true);
        return view('lpb.index', compact('financial'));
    }

    public function reportPdf(Request $request)
    {
        $this->authorize('viewAny', Lpb::class);

        $filters = collect($request->input('filters', []))->filter(fn($value) => $value !== '');
        $search = trim((string) $request->input('search', ''));
        $query = Lpb::with(['pembelian.supplier', 'gudang', 'user'])->latest('tanggal');

        $receiptType = strtoupper((string) $request->input('jenis_lpb', ''));
        if (in_array($receiptType, ['3', 'SERVICE_BAP'], true)) {
            $query->where(fn($builder) => $builder
                ->where('jenis_lpb', 3)
                ->orWhere('document_type', 'SERVICE_BAP'));
        } elseif (in_array($receiptType, ['1', 'GOODS'], true)) {
            $query->where(fn($builder) => $builder
                ->where('jenis_lpb', 1)
                ->orWhere('document_type', 'GOODS')
                ->orWhereNull('document_type'));
        }

        if ($search !== '') {
            $query->where(fn($q) => $q->where('id_lpb', 'like', "%{$search}%")
                ->orWhere('tanggal', 'like', "%{$search}%")
                ->orWhere('no_po', 'like', "%{$search}%")
                ->orWhere('no_sj', 'like', "%{$search}%")
                ->orWhereHas('pembelian.supplier', fn($supplier) => $supplier->where('nama', 'like', "%{$search}%"))
                ->orWhereHas('user', fn($user) => $user->where('name', 'like', "%{$search}%")));
        }

        foreach (['id_lpb', 'tanggal', 'no_po', 'no_sj'] as $field) {
            if ($filters->has($field)) {
                $query->where($field, 'like', "%{$filters[$field]}%");
            }
        }
        if ($filters->has('supplier_nama')) {
            $query->whereHas('pembelian.supplier', fn($supplier) => $supplier->where('nama', 'like', "%{$filters['supplier_nama']}%"));
        }
        if ($filters->has('gudang_nama')) {
            $query->whereHas('gudang', fn($gudang) => $gudang->where('nama', 'like', "%{$filters['gudang_nama']}%"));
        }
        if ($filters->has('user_nama')) {
            $query->whereHas('user', fn($user) => $user->where('name', 'like', "%{$filters['user_nama']}%"));
        }

        $rows = $query->limit(5000)->get()->map(fn($row) => [
            'id_lpb' => $row->id_lpb,
            'jenis_lpb' => $row->document_type === 'SERVICE_BAP' ? 'BAP Jasa' : 'LPB Barang',
            'tanggal' => $row->tanggal,
            'no_po' => $row->no_po,
            'supplier_nama' => $row->pembelian->supplier->nama ?? '-',
            'gudang_nama' => $row->gudang->nama ?? '-',
            'no_sj' => $row->no_sj ?: '-',
            'user_nama' => $row->user->name ?? "User #{$row->id_user}",
        ]);

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Daftar Penerimaan LPB & BAP',
            'columns' => [
                ['key' => 'id_lpb', 'label' => 'No LPB', 'align' => 'left'],
                ['key' => 'jenis_lpb', 'label' => 'Jenis', 'align' => 'left'],
                ['key' => 'tanggal', 'label' => 'Tanggal', 'align' => 'left'],
                ['key' => 'no_po', 'label' => 'No PO', 'align' => 'left'],
                ['key' => 'supplier_nama', 'label' => 'Supplier', 'align' => 'left'],
                ['key' => 'gudang_nama', 'label' => 'Gudang', 'align' => 'left'],
                ['key' => 'no_sj', 'label' => 'No SJ', 'align' => 'left'],
                ['key' => 'user_nama', 'label' => 'Petugas', 'align' => 'left'],
            ],
            'rows' => $rows,
            'search' => $search,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream('daftar-lpb.pdf');
    }

    public function create()
    {
        $this->authorize('create', Lpb::class);

        $pos = Pembelian::with(['supplier', 'details'])
            ->where('status', '!=', 2)
            ->orderBy('no_po', 'desc')
            ->get();
        $kategoris = KategoriBahan::all();
        $documentNumber = $this->numbers->external('LPB');

        return view('lpb.create', compact('pos', 'kategoris', 'documentNumber'));
    }

    public function getPoDetail($no_po)
    {
        $this->authorize('create', Lpb::class);
        $po = Pembelian::where('no_po', $no_po)->with(['supplier', 'gudang', 'details.bahan'])->firstOrFail();

        $items = $po->details->map(function ($detail) {
            $selisih = $detail->jumlah - $detail->diterima;
            return [
                'pembelian_detail_id' => $detail->id,
                'bahan_id'            => $detail->bahan_id,
                'nama_bahan'          => $detail->bahan->nama ?? '-',
                'id_kategori'         => $detail->bahan->tipe_barang ?? null,
                'jumlah_po'           => $detail->jumlah,
                'diterima'            => $detail->diterima,
                'sisa'                => $selisih > 0 ? $selisih : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'po'      => [
                'no_po' => $po->no_po,
                'supplier' => ['nama' => $po->supplier->nama ?? '-'],
                'gudang' => ['id' => $po->gudang_id, 'nama' => $po->gudang->nama ?? '-'],
            ],
            'items'   => $items
        ]);
    }

    public function store(StoreLpbRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();

        $po = Pembelian::where('no_po', $validated['no_po'])->with('details')->firstOrFail();

        $overItems = [];
        foreach ($validated['details'] as $item) {
            $poDetail = $po->details->where('bahan_id', $item['id_bahan'])->first();
            if ($poDetail) {
                $sisaBelumDiterima = $poDetail->jumlah - $poDetail->diterima;
                if ($item['jumlah_barang_diterima'] > $sisaBelumDiterima) {
                    $bahan = Bahan::find($item['id_bahan']);
                    $overItems[] = [
                        'nama'       => $bahan->nama ?? 'Bahan #' . $item['id_bahan'],
                        'minta_sisa' => $sisaBelumDiterima > 0 ? $sisaBelumDiterima : 0,
                        'input'      => $item['jumlah_barang_diterima'],
                    ];
                }
            }
        }

        if (!empty($overItems) && empty($validated['confirm_over_receive'])) {
            return response()->json([
                'requires_confirmation' => true,
                'message'               => 'Jumlah barang yang diterima melebihi sisa pesanan PO.',
                'over_items'            => $overItems
            ], 422);
        }

        $lpb = DB::transaction(function () use ($validated, $user, $po) {
            $idLpb = $validated['id_lpb'];

            $lpb = Lpb::create([
                'id_lpb'      => $idLpb,
                'tanggal'     => $validated['tanggal'],
                'no_po'       => $validated['no_po'],
                'gudang_id'   => $po->gudang_id,
                'no_sj'       => $validated['no_sj'],
                'id_user'     => $user->id,
                'flag'        => 0,
                'no_invoice'  => $validated['no_invoice'] ?? null,
                'status'      => 0,
                'jenis_lpb'   => $validated['jenis_lpb'] ?? 1,
                'ulang'       => 0,
                'kunci'       => 0,
                'cetakan'     => 0,
                'cetak_ulang' => 0,
            ]);

            foreach ($validated['details'] as $item) {
                $poDetail = Pembeliandetail::where('no_po', $validated['no_po'])
                    ->where('bahan_id', $item['id_bahan'])
                    ->lockForUpdate()
                    ->firstOrFail();
                $lockedRemaining = (float) $poDetail->jumlah - (float) $poDetail->diterima;
                if (
                    (float) $item['jumlah_barang_diterima'] > $lockedRemaining
                    && empty($validated['confirm_over_receive'])
                ) {
                    abort(422, 'Jumlah penerimaan berubah atau melebihi sisa PO. Periksa kembali lalu konfirmasi over-receive.');
                }
                $bahan = Bahan::findOrFail($item['id_bahan']);
                if ((int) $item['id_kategori'] !== (int) $bahan->tipe_barang) {
                    throw new \RuntimeException("Kategori bahan {$bahan->nama} tidak sesuai master.");
                }
                $unitPrice = (float) $poDetail->harga;
                $lpbDetail = LpbDetail::create([
                    'id_lpb'                 => $idLpb,
                    'id_bahan'               => $item['id_bahan'],
                    'id_kategori'            => $item['id_kategori'] ?? null,
                    'jumlah_barang_diterima' => $item['jumlah_barang_diterima'],
                    'lot_number'             => $item['lot_number'] ?? null,
                    'harga'                  => $unitPrice,
                    'nilai_awal'             => $item['jumlah_barang_diterima'] * $unitPrice,
                    'jumlah_dipakai'         => 0,
                    'jumlah_tersisa'         => $item['jumlah_barang_diterima'],
                    'flag_dipakai'           => 1,
                ]);
                InventoryLayer::create([
                    'bahan_id' => $item['id_bahan'],
                    'gudang_id' => $po->gudang_id,
                    'source_type' => 'LPB_DETAIL',
                    'source_id' => $lpbDetail->id,
                    'transaction_date' => $lpb->tanggal,
                    'initial_quantity' => $item['jumlah_barang_diterima'],
                    'remaining_quantity' => $item['jumlah_barang_diterima'],
                    'unit_cost' => $unitPrice,
                ]);

                $this->stokGudang->masuk((int) $po->gudang_id, (int) $item['id_bahan'], (float) $item['jumlah_barang_diterima'], $unitPrice, 'PENERIMAAN', 'LPB', $lpb->id, $lpb->id_lpb);

                if ($poDetail) {
                    $poDetail->increment('diterima', $item['jumlah_barang_diterima']);

                    $sisaPoSaja = max(0, $poDetail->jumlah - ($poDetail->diterima - $item['jumlah_barang_diterima']));
                    $potongOnPurchase = min($sisaPoSaja, $item['jumlah_barang_diterima']);

                    if ($potongOnPurchase > 0) {
                        Bahan::where('id', $item['id_bahan'])->decrement('stok_onpurchase', $potongOnPurchase);
                        $this->stokGudang->kurangiPesanan((int) $po->gudang_id, (int) $item['id_bahan'], (float) $potongOnPurchase);
                    }
                }
            }

            $this->accounting->postLpb($lpb);
            $lpb->update(['kunci' => 1, 'status' => 1]);

            return $lpb;
        });

        return response()->json([
            'success' => true,
            'message' => 'Penerimaan barang (LPB) berhasil disimpan dan dicatat ke Jurnal COA.',
            'data'    => $lpb
        ], 201);
    }

    public function show(Request $request, $lpb)
    {
        $lpbData = Lpb::where('id', $lpb)->orWhere('id_lpb', $lpb)
            ->with([
                'details.bahan',
                'details.kategori',
                'serviceDetails.servicePoDetail.category',
                'serviceDetails.kategori',
                'serviceDetails.allocations',
                'pembelian.supplier',
                'user',
            ])
            ->firstOrFail();

        $this->authorize('view', $lpbData);

        if (!$request->expectsJson() && !$request->ajax()) {
            return view('lpb.show-page', [
                'lpb' => $lpbData,
                'financial' => in_array((int) $request->user()->type, [5, 33], true),
            ]);
        }

        if (!in_array((int) $request->user()->type, [5, 33], true)) {
            $lpbData->details->each->makeHidden(['harga', 'nilai_awal']);
            $lpbData->serviceDetails->each->makeHidden(['amount']);
            $lpbData->unsetRelation('pembelian');
        }

        return response()->json([
            'success' => true,
            'data'    => $lpbData
        ]);
    }

    public function update(UpdateLpbRequest $request, $lpb)
    {
        $lpbData = Lpb::where('id', $lpb)->orWhere('id_lpb', $lpb)
            ->with('details')
            ->firstOrFail();

        $this->authorize('update', $lpbData);

        $validated = $request->validated();
        $po = Pembelian::where('no_po', $lpbData->no_po)->with('details')->firstOrFail();

        $overItems = [];
        foreach ($validated['details'] as $item) {
            $poDetail = $po->details->where('bahan_id', $item['id_bahan'])->first();
            if ($poDetail) {
                $oldDetail = $lpbData->details->where('id_bahan', $item['id_bahan'])->first();
                $oldQty = $oldDetail ? $oldDetail->jumlah_barang_diterima : 0;
                $sisaBelumDiterima = ($poDetail->jumlah - $poDetail->diterima) + $oldQty;

                if ($item['jumlah_barang_diterima'] > $sisaBelumDiterima) {
                    $bahan = Bahan::find($item['id_bahan']);
                    $overItems[] = [
                        'nama'       => $bahan->nama ?? 'Bahan #' . $item['id_bahan'],
                        'minta_sisa' => $sisaBelumDiterima > 0 ? $sisaBelumDiterima : 0,
                        'input'      => $item['jumlah_barang_diterima'],
                    ];
                }
            }
        }

        if (!empty($overItems) && empty($validated['confirm_over_receive'])) {
            return response()->json([
                'requires_confirmation' => true,
                'message'               => 'Jumlah barang yang diterima melebihi sisa pesanan PO.',
                'over_items'            => $overItems
            ], 422);
        }

        DB::transaction(function () use ($validated, $lpbData, $po) {
            foreach ($lpbData->details as $oldDetail) {
                Bahan::where('id', $oldDetail->id_bahan)->decrement('stok_onhand', $oldDetail->jumlah_barang_diterima);

                $poDetail = Pembeliandetail::where('no_po', $lpbData->no_po)
                    ->where('bahan_id', $oldDetail->id_bahan)
                    ->lockForUpdate()
                    ->first();

                if ($poDetail) {
                    $poDetail->decrement('diterima', $oldDetail->jumlah_barang_diterima);

                    $kembalikanOnPurchase = min($poDetail->jumlah, $oldDetail->jumlah_barang_diterima);
                    if ($kembalikanOnPurchase > 0) {
                        Bahan::where('id', $oldDetail->id_bahan)->increment('stok_onpurchase', $kembalikanOnPurchase);
                    }
                }
            }

            $lpbData->details()->delete();

            $lpbData->update([
                'tanggal'    => $validated['tanggal'],
                'no_sj'      => $validated['no_sj'],
                'no_invoice' => $validated['no_invoice'] ?? null,
                'jenis_lpb'  => $validated['jenis_lpb'] ?? $lpbData->jenis_lpb,
            ]);

            foreach ($validated['details'] as $item) {
                LpbDetail::create([
                    'id_lpb'                 => $lpbData->id_lpb,
                    'id_bahan'               => $item['id_bahan'],
                    'id_kategori'            => $item['id_kategori'] ?? null,
                    'jumlah_barang_diterima' => $item['jumlah_barang_diterima'],
                    'lot_number'             => $item['lot_number'] ?? null,
                    'harga'                  => $item['harga'] ?? null,
                    'jumlah_dipakai'         => 0,
                    'flag_dipakai'           => 1,
                ]);

                Bahan::where('id', $item['id_bahan'])->increment('stok_onhand', $item['jumlah_barang_diterima']);

                $poDetail = Pembeliandetail::where('no_po', $lpbData->no_po)
                    ->where('bahan_id', $item['id_bahan'])
                    ->lockForUpdate()
                    ->first();

                if ($poDetail) {
                    $poDetail->increment('diterima', $item['jumlah_barang_diterima']);

                    $sisaPoSaja = max(0, $poDetail->jumlah - ($poDetail->diterima - $item['jumlah_barang_diterima']));
                    $potongOnPurchase = min($sisaPoSaja, $item['jumlah_barang_diterima']);

                    if ($potongOnPurchase > 0) {
                        Bahan::where('id', $item['id_bahan'])->decrement('stok_onpurchase', $potongOnPurchase);
                    }
                }
            }

            $this->syncJurnalPenerimaanBarang($lpbData);
        });

        return response()->json([
            'success' => true,
            'message' => 'Penerimaan barang (LPB) berhasil diperbarui.'
        ]);
    }

    public function destroy($lpb)
    {
        $lpbData = Lpb::where('id', $lpb)->orWhere('id_lpb', $lpb)
            ->with('details')
            ->firstOrFail();

        $this->authorize('delete', $lpbData);

        DB::transaction(function () use ($lpbData) {
            foreach ($lpbData->details as $detail) {
                Bahan::where('id', $detail->id_bahan)->decrement('stok_onhand', $detail->jumlah_barang_diterima);

                $poDetail = Pembeliandetail::where('no_po', $lpbData->no_po)
                    ->where('bahan_id', $detail->id_bahan)
                    ->lockForUpdate()
                    ->first();

                if ($poDetail) {
                    $poDetail->decrement('diterima', $detail->jumlah_barang_diterima);

                    $kembalikanOnPurchase = min($poDetail->jumlah, $detail->jumlah_barang_diterima);
                    if ($kembalikanOnPurchase > 0) {
                        Bahan::where('id', $detail->id_bahan)->increment('stok_onpurchase', $kembalikanOnPurchase);
                    }
                }
            }

            $jurnal = Jurnal::where('sumber_transaksi', 'LPB')
                ->where(function ($q) use ($lpbData) {
                    $q->where('reff_id', $lpbData->id)
                        ->orWhere('no_jurnal', $lpbData->id_lpb);
                })
                ->first();

            if ($jurnal) {
                $jurnal->details()->delete();
                $jurnal->delete();
            }

            $lpbData->details()->delete();
            $lpbData->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Penerimaan barang (LPB) berhasil dibatalkan/dihapus.'
        ]);
    }

    public function storeDetail(StoreLpbDetailRequest $request, $lpb)
    {
        $lpbData = Lpb::where('id', $lpb)->orWhere('id_lpb', $lpb)->firstOrFail();
        $this->authorize('update', $lpbData);

        $validated = $request->validated();

        $detail = DB::transaction(function () use ($validated, $lpbData) {
            $newDetail = LpbDetail::create([
                'id_lpb'                 => $lpbData->id_lpb,
                'id_bahan'               => $validated['id_bahan'],
                'id_kategori'            => $validated['id_kategori'] ?? null,
                'jumlah_barang_diterima' => $validated['jumlah_barang_diterima'],
                'lot_number'             => $validated['lot_number'] ?? null,
                'harga'                  => $validated['harga'] ?? null,
                'jumlah_dipakai'         => 0,
                'flag_dipakai'           => 1,
            ]);

            Bahan::where('id', $validated['id_bahan'])->increment('stok_onhand', $validated['jumlah_barang_diterima']);

            $poDetail = Pembeliandetail::where('no_po', $lpbData->no_po)
                ->where('bahan_id', $validated['id_bahan'])
                ->lockForUpdate()
                ->first();

            if ($poDetail) {
                $poDetail->increment('diterima', $validated['jumlah_barang_diterima']);

                $sisaPoSaja = max(0, $poDetail->jumlah - ($poDetail->diterima - $validated['jumlah_barang_diterima']));
                $potongOnPurchase = min($sisaPoSaja, $validated['jumlah_barang_diterima']);

                if ($potongOnPurchase > 0) {
                    Bahan::where('id', $validated['id_bahan'])->decrement('stok_onpurchase', $potongOnPurchase);
                }
            }

            $this->syncJurnalPenerimaanBarang($lpbData);

            return $newDetail;
        });

        return response()->json([
            'success' => true,
            'message' => 'Detail penerimaan barang berhasil ditambahkan.',
            'data'    => $detail
        ], 201);
    }

    public function updateDetail(UpdateLpbDetailRequest $request, $lpb, $detailId)
    {
        $lpbData = Lpb::where('id', $lpb)->orWhere('id_lpb', $lpb)->firstOrFail();
        $this->authorize('update', $lpbData);

        $detail = LpbDetail::where('id', $detailId)
            ->where('id_lpb', $lpbData->id_lpb)
            ->firstOrFail();

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $lpbData, $detail) {
            $oldQty = $detail->jumlah_barang_diterima;
            $newQty = $validated['jumlah_barang_diterima'];
            $diff = $newQty - $oldQty;

            $updateData = [
                'jumlah_barang_diterima' => $newQty,
                'lot_number'             => $validated['lot_number'] ?? $detail->lot_number,
                'harga'                  => $validated['harga'] ?? $detail->harga,
            ];

            if (isset($validated['id_kategori'])) {
                $updateData['id_kategori'] = $validated['id_kategori'];
            }

            $detail->update($updateData);

            if ($diff != 0) {
                if ($diff > 0) {
                    Bahan::where('id', $detail->id_bahan)->increment('stok_onhand', $diff);
                } else {
                    Bahan::where('id', $detail->id_bahan)->decrement('stok_onhand', abs($diff));
                }

                $poDetail = Pembeliandetail::where('no_po', $lpbData->no_po)
                    ->where('bahan_id', $detail->id_bahan)
                    ->lockForUpdate()
                    ->first();

                if ($poDetail) {
                    if ($diff > 0) {
                        $poDetail->increment('diterima', $diff);

                        $sisaPoSaja = max(0, $poDetail->jumlah - ($poDetail->diterima - $diff));
                        $potongOnPurchase = min($sisaPoSaja, $diff);

                        if ($potongOnPurchase > 0) {
                            Bahan::where('id', $detail->id_bahan)->decrement('stok_onpurchase', $potongOnPurchase);
                        }
                    } else {
                        $poDetail->decrement('diterima', abs($diff));

                        $kembalikanOnPurchase = min($poDetail->jumlah, abs($diff));
                        if ($kembalikanOnPurchase > 0) {
                            Bahan::where('id', $detail->id_bahan)->increment('stok_onpurchase', $kembalikanOnPurchase);
                        }
                    }
                }
            }

            $this->syncJurnalPenerimaanBarang($lpbData);
        });

        return response()->json([
            'success' => true,
            'message' => 'Detail penerimaan barang berhasil diperbarui.'
        ]);
    }

    public function destroyDetail($lpb, $detailId)
    {
        $lpbData = Lpb::where('id', $lpb)->orWhere('id_lpb', $lpb)->firstOrFail();
        $this->authorize('update', $lpbData);

        $detail = LpbDetail::where('id', $detailId)
            ->where('id_lpb', $lpbData->id_lpb)
            ->firstOrFail();

        DB::transaction(function () use ($lpbData, $detail) {
            Bahan::where('id', $detail->id_bahan)->decrement('stok_onhand', $detail->jumlah_barang_diterima);

            $poDetail = Pembeliandetail::where('no_po', $lpbData->no_po)
                ->where('bahan_id', $detail->id_bahan)
                ->lockForUpdate()
                ->first();

            if ($poDetail) {
                $poDetail->decrement('diterima', $detail->jumlah_barang_diterima);

                $kembalikanOnPurchase = min($poDetail->jumlah, $detail->jumlah_barang_diterima);
                if ($kembalikanOnPurchase > 0) {
                    Bahan::where('id', $detail->id_bahan)->increment('stok_onpurchase', $kembalikanOnPurchase);
                }
            }

            $detail->delete();

            $this->syncJurnalPenerimaanBarang($lpbData);
        });

        return response()->json([
            'success' => true,
            'message' => 'Detail penerimaan barang berhasil dihapus.'
        ]);
    }

    private function syncJurnalPenerimaanBarang(Lpb $lpb): void
    {
        $this->accounting->postLpb($lpb);
    }
}
