<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\Bahan;
use App\Models\Gudang;
use App\Models\RequestDetail;
use App\Http\Requests\StorePembelianRequest;
use App\Http\Requests\UpdatePembelianRequest;
use App\Policies\PembelianPolicy;
use App\Services\DocumentNumberService;
use App\Services\StokGudangService;
use App\Traits\CalculatesPembelianTotals;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TCPDF;
use Barryvdh\DomPDF\Facade\Pdf;

class PembelianController extends Controller
{
    public function __construct(private DocumentNumberService $numbers, private StokGudangService $stokGudang) {}
    use CalculatesPembelianTotals;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Pembelian::class);

        if ($request->ajax()) {
            $bulan = $request->input('bulan');
            $tahun = $request->input('tahun');

            $query = Pembelian::with(['supplier', 'details.bahan'])
                ->when($bulan != '0' && !empty($bulan), function ($q) use ($bulan) {
                    return $q->whereMonth('tanggal', $bulan);
                })
                ->when(!empty($tahun), function ($q) use ($tahun) {
                    return $q->whereYear('tanggal', $tahun);
                });

            return datatables()->of($query)
                ->filterColumn('supplier.nama', function ($query, $keyword) {
                    $query->whereHas('supplier', function ($supplier) use ($keyword) {
                        $supplier->where('nama', 'like', "%{$keyword}%");
                    });
                })
                ->addColumn('nama', function ($row) {
                    return $row->supplier->nama ?? '-';
                })
                ->addColumn('kunci', function ($row) {
                    return $row->kunci;
                })
                ->make(true);
        }

        $documentNumber = $this->numbers->financial('PO');
        $gudangs = Gudang::where('aktif', true)->where('jenis', Gudang::NORMAL)->orderBy('nama')->get();
        return view('pembelian.index', compact('documentNumber', 'gudangs'));
    }

    public function reportPdf(Request $request)
    {
        $this->authorize('viewAny', Pembelian::class);

        $filters = collect($request->input('filters', []))->filter(fn ($value) => $value !== '');
        $search = trim((string) $request->input('search', ''));
        $query = Pembelian::with('supplier')
            ->when($request->filled('bulan') && $request->bulan !== '0', fn ($q) => $q->whereMonth('tanggal', $request->bulan))
            ->when($request->filled('tahun'), fn ($q) => $q->whereYear('tanggal', $request->tahun))
            ->latest('tanggal');

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('no_po', 'like', "%{$search}%")
                ->orWhere('tanggal', 'like', "%{$search}%")
                ->orWhere('grand_total', 'like', "%{$search}%")
                ->orWhereHas('supplier', fn ($supplier) => $supplier->where('nama', 'like', "%{$search}%")));
        }

        foreach (['no_po', 'tanggal', 'grand_total', 'status'] as $field) {
            if ($filters->has($field)) {
                $query->where($field, 'like', "%{$filters[$field]}%");
            }
        }
        if ($filters->has('nama')) {
            $query->whereHas('supplier', fn ($supplier) => $supplier->where('nama', 'like', "%{$filters['nama']}%"));
        }

        $rows = $query->limit(5000)->get()->map(fn ($row) => [
            'no_po' => $row->no_po,
            'tanggal' => $row->tanggal,
            'nama' => $row->supplier->nama ?? '-',
            'grand_total' => 'Rp '.number_format($row->grand_total, 0, ',', '.'),
            'status' => (int) $row->status === 2 ? 'Closed' : 'Open',
        ]);

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Daftar Pembelian',
            'columns' => [
                ['key' => 'no_po', 'label' => 'No PO', 'align' => 'left'],
                ['key' => 'tanggal', 'label' => 'Tanggal', 'align' => 'left'],
                ['key' => 'nama', 'label' => 'Supplier', 'align' => 'left'],
                ['key' => 'grand_total', 'label' => 'Grand Total', 'align' => 'right'],
                ['key' => 'status', 'label' => 'Status', 'align' => 'left'],
            ],
            'rows' => $rows,
            'search' => $search,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream('daftar-pembelian.pdf');
    }

    public function store(StorePembelianRequest $request)
    {
        $validated = $request->validated();

        $pembelian = DB::transaction(function () use ($validated) {
            $noPo = $validated['no_po'];
            $ppnPercent = $validated['is_ppn'] ? PembelianPolicy::PPN_RATE : 0;

            $pembelian = Pembelian::create([
                'no_po'           => $noPo,
                'tanggal'         => $validated['tanggal'],
                'supplier_id'     => $validated['supplier_id'],
                'gudang_id'       => $validated['gudang_id'],
                'no_order'        => $validated['no_order'] ?? '-',
                'untuk_perhatian' => $validated['untuk_perhatian'] ?? '-',
                'term'            => $validated['term'] ?? '-',
                'notes'           => $validated['notes'] ?? '-',
                'ppn'             => $ppnPercent,
                'diskon'          => $validated['diskon'] ?? 0,
                'ongkir'          => $validated['ongkir'] ?? 0,
                'input_label'     => $validated['input_label'] ?? 'Freight Handling',
                'jenis'           => 0,
            ]);

            foreach ($validated['details'] as $item) {
                $subExclude = $item['jumlah'] * $item['harga'];
                $subPpn = round(($subExclude * $ppnPercent) / 100, 2);

                $pembelian->details()->create([
                    'bahan_id'          => $item['bahan_id'],
                    'jumlah'            => $item['jumlah'],
                    'harga'             => $item['harga'],
                    'exclude'           => $subExclude,
                    'ppn'               => $subPpn,
                    'include'           => $subExclude + $subPpn,
                    'request_detail_id' => $item['request_detail_id'] ?? null,
                    'jenis'             => 0,
                ]);

                if (!empty($item['bahan_id'])) {
                    Bahan::where('id', $item['bahan_id'])->increment('planning', $item['jumlah']);
                    $this->stokGudang->tambahPesanan((int) $validated['gudang_id'], (int) $item['bahan_id'], (float) $item['jumlah']);
                }

                if (!empty($item['request_detail_id'])) {
                    $reqDetail = RequestDetail::find($item['request_detail_id']);
                    if ($reqDetail) {
                        $reqDetail->update([
                            'realisasi' => $reqDetail->pembelianDetails()->sum('jumlah')
                        ]);
                    }
                }
            }

            return $this->recalculatePembelianTotals($pembelian);
        });

        return response()->json([
            'success' => true,
            'message' => 'Pesanan Pembelian (PO) berhasil dibuat.',
            'data'    => $pembelian,
            'next_document_number' => $this->numbers->financial('PO'),
        ], 201);
    }

    public function show(Request $request, $no_po)
    {
        $pembelian = Pembelian::where('no_po', $no_po)->with(['supplier', 'gudang', 'details.bahan', 'details.requestDetail'])->firstOrFail();
        $this->authorize('view', $pembelian);

        if (!$request->expectsJson() && !$request->ajax()) {
            return view('pembelian.show-page', ['pembelian' => $pembelian]);
        }

        return response()->json([
            'success' => true,
            'data'    => $pembelian
        ]);
    }

    public function update(UpdatePembelianRequest $request, $no_po)
    {
        $pembelian = Pembelian::where('no_po', $no_po)->with('details')->firstOrFail();
        $this->authorize('update', $pembelian);

        $validated = $request->validated();

        DB::transaction(function () use ($pembelian, $validated) {
            if ((int) $pembelian->gudang_id !== (int) $validated['gudang_id'] && $pembelian->details->sum('diterima') > 0) {
                abort(422, 'Gudang tujuan tidak dapat diubah setelah PO memiliki penerimaan.');
            }
            $this->archiveHistoryIfNeeded($pembelian);

            $oldReqDetails = $pembelian->details->pluck('request_detail_id')->filter()->unique();

            foreach ($pembelian->details as $oldDetail) {
                if ($oldDetail->bahan_id) {
                    Bahan::where('id', $oldDetail->bahan_id)->decrement('planning', $oldDetail->jumlah);
                    $this->stokGudang->kurangiPesanan((int) $pembelian->gudang_id, (int) $oldDetail->bahan_id, max(0, (float) $oldDetail->jumlah - (float) $oldDetail->diterima));
                }
            }

            $ppnPercent = $validated['is_ppn'] ? PembelianPolicy::PPN_RATE : 0;

            $pembelian->update([
                'tanggal'         => $validated['tanggal'],
                'supplier_id'     => $validated['supplier_id'],
                'gudang_id'       => $validated['gudang_id'],
                'no_order'        => $validated['no_order'] ?? '-',
                'untuk_perhatian' => $validated['untuk_perhatian'] ?? '-',
                'term'            => $validated['term'] ?? '-',
                'notes'           => $validated['notes'] ?? '-',
                'ppn'             => $ppnPercent,
                'diskon'          => $validated['diskon'] ?? 0,
                'ongkir'          => $validated['ongkir'] ?? 0,
                'input_label'     => $validated['input_label'] ?? 'Freight Handling',
            ]);

            $pembelian->details()->delete();

            $newReqDetails = collect();

            foreach ($validated['details'] as $item) {
                $subExclude = $item['jumlah'] * $item['harga'];
                $subPpn = round(($subExclude * $ppnPercent) / 100, 2);

                $pembelian->details()->create([
                    'bahan_id'          => $item['bahan_id'],
                    'jumlah'            => $item['jumlah'],
                    'harga'             => $item['harga'],
                    'exclude'           => $subExclude,
                    'ppn'               => $subPpn,
                    'include'           => $subExclude + $subPpn,
                    'request_detail_id' => $item['request_detail_id'] ?? null,
                    'jenis'             => 0,
                ]);

                if (!empty($item['bahan_id'])) {
                    Bahan::where('id', $item['bahan_id'])->increment('planning', $item['jumlah']);
                    $this->stokGudang->tambahPesanan((int) $validated['gudang_id'], (int) $item['bahan_id'], (float) $item['jumlah']);
                }

                if (!empty($item['request_detail_id'])) {
                    $newReqDetails->push($item['request_detail_id']);
                }
            }

            $affectedReqDetails = $oldReqDetails->merge($newReqDetails)->unique();
            foreach ($affectedReqDetails as $reqId) {
                $reqDetail = RequestDetail::find($reqId);
                if ($reqDetail) {
                    $reqDetail->update([
                        'realisasi' => $reqDetail->pembelianDetails()->sum('jumlah')
                    ]);
                }
            }

            $this->recalculatePembelianTotals($pembelian);
        });

        return response()->json([
            'success' => true,
            'message' => 'Pesanan Pembelian (PO) berhasil diperbarui.',
            'data'    => $pembelian
        ]);
    }

    public function destroy($no_po)
    {
        $pembelian = Pembelian::where('no_po', $no_po)->with('details')->firstOrFail();
        $this->authorize('delete', $pembelian);

        DB::transaction(function () use ($pembelian) {
            $reqDetails = $pembelian->details->pluck('request_detail_id')->filter()->unique();

            foreach ($pembelian->details as $detail) {
                if ($detail->bahan_id) {
                    Bahan::where('id', $detail->bahan_id)->decrement('planning', $detail->jumlah);
                    $this->stokGudang->kurangiPesanan((int) $pembelian->gudang_id, (int) $detail->bahan_id, max(0, (float) $detail->jumlah - (float) $detail->diterima));
                }
            }

            $pembelian->details()->delete();
            $pembelian->delete();

            foreach ($reqDetails as $reqId) {
                $reqDetail = RequestDetail::find($reqId);
                if ($reqDetail) {
                    $reqDetail->update([
                        'realisasi' => $reqDetail->pembelianDetails()->sum('jumlah')
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Pesanan Pembelian (PO) berhasil dihapus.'
        ]);
    }

    public function close(Request $request, $no_po)
    {
        $pembelian = Pembelian::where('no_po', $no_po)->with('details')->firstOrFail();
        $this->authorize('update', $pembelian);

        if ($pembelian->status == 2) {
            return response()->json([
                'success' => false,
                'message' => 'PO sudah dalam status tertutup (Closed).'
            ], 422);
        }

        DB::transaction(function () use ($pembelian) {
            $pembelian->update(['status' => 2]);

            foreach ($pembelian->details as $detail) {
                $selisih = $detail->jumlah - $detail->diterima;
                if ($selisih > 0 && $detail->bahan_id) {
                    Bahan::where('id', $detail->bahan_id)->decrement('stok_onpurchase', $selisih);
                    $this->stokGudang->kurangiPesanan((int) $pembelian->gudang_id, (int) $detail->bahan_id, (float) $selisih);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'PO Berhasil ditutup (Closed).'
        ]);
    }

    public function cetak($no_po)
    {
        $pembelian = Pembelian::where('no_po', $no_po)->with(['supplier', 'details'])->firstOrFail();
        $this->authorize('view', $pembelian);

        $pembelian->increment('cetak');

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();

        $html = '<h2 align="center">PURCHASE ORDER (PO)</h2>';
        $html .= '<p><b>No PO:</b> ' . $pembelian->no_po . '<br><b>Supplier:</b> ' . ($pembelian->supplier->nama ?? '-') . '</p>';

        $pdf->writeHTML($html, true, false, true, false, '');

        return response($pdf->Output('PO-' . $pembelian->no_po . '.pdf', 'I'))
            ->header('Content-Type', 'application/pdf');
    }

    private function archiveHistoryIfNeeded(Pembelian $pembelian): void
    {
        if ($pembelian->cetak < 1) {
            return;
        }

        $noRevisi = $pembelian->no_po . '-' . $pembelian->counter_asli;

        DB::table('pembelian_histories')->updateOrInsert(
            ['no_revisi' => $noRevisi],
            [
                'action'          => 'REVISION',
                'archived_at'     => now(),
                'no_po'           => $pembelian->no_po,
                'pembelian_id'    => $pembelian->id,
                'tanggal'         => $pembelian->tanggal,
                'supplier_id'     => $pembelian->supplier_id,
                'no_order'        => $pembelian->no_order,
                'untuk_perhatian' => $pembelian->untuk_perhatian,
                'term'            => $pembelian->term,
                'notes'           => $pembelian->notes,
                'ppn'             => $pembelian->ppn,
                'total_exclude'   => $pembelian->total_exclude,
                'total_ppn'       => $pembelian->total_ppn,
                'total_include'   => $pembelian->total_include,
                'diskon'          => $pembelian->diskon,
                'ongkir'          => $pembelian->ongkir,
                'grand_total'     => $pembelian->grand_total,
                'status'          => $pembelian->status,
                'term_pengiriman' => $pembelian->term_pengiriman,
                'jenis'           => 0,
                'input_label'     => $pembelian->input_label,
                'cetak'           => $pembelian->cetak,
                'kunci'           => $pembelian->kunci,
                'counter_asli'    => $pembelian->counter_asli,
                'cetak_ulang'     => 1,
                'created_at'      => $pembelian->created_at,
                'updated_at'      => now(),
            ]
        );

        DB::table('pembelian_detail_histories')->where('no_revisi', $noRevisi)->delete();

        foreach ($pembelian->details as $detail) {
            DB::table('pembelian_detail_histories')->insert([
                'no_revisi'           => $noRevisi,
                'pembelian_detail_id' => $detail->id,
                'no_po'               => $detail->no_po,
                'bahan_id'            => $detail->bahan_id,
                'jumlah'              => $detail->jumlah,
                'harga'               => $detail->harga,
                'exclude'             => $detail->exclude,
                'ppn'                 => $detail->ppn,
                'include'             => $detail->include,
                'diterima'            => $detail->diterima,
                'request_detail_id'   => $detail->request_detail_id,
                'jenis'               => 0,
                'created_at'          => $detail->created_at,
                'updated_at'          => now(),
            ]);
        }

        $pembelian->update(['cetak_ulang' => 1]);
    }
}
