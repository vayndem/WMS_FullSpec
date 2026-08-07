<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\DetailPOModel;
use App\Models\Permintaan;
use App\Models\POModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use TCPDF;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use App\Exports\PoKertasExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LaporanOpnameExport;
use App\Exports\ExportLaporan;

class PurchasingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $jenis = $request->query('jenis', 3);
            $search = $request->input('search.value');

            $permintaan = Permintaan::join('bahan', 'permintaan.id_bahan', '=', 'bahan.id')->whereColumn('permintaan.jumlah_order', '>', 'permintaan.realisasi')->orderBy('permintaan.id', 'desc')->select('permintaan.id', 'permintaan.id_bahan', 'permintaan.jumlah_order', 'permintaan.realisasi', 'permintaan.finish', 'permintaan.created_at', 'bahan.nama as bahan_nama', 'bahan.harga as harga', 'bahan.satuan as satuan');

            if ($jenis != 3) {
                $permintaan->where('bahan.jenis', $jenis);
            }

            if (!empty($search)) {
                $permintaan->where(function ($query) use ($search) {
                    $query->where('bahan.nama', 'like', "%$search%")->orWhere('permintaan.id', 'like', "%$search%");
                });
            }

            return datatables()
                ->of($permintaan)
                ->addIndexColumn()
                ->addColumn('bahan', function ($row) {
                    return $row->bahan_nama ?? '-';
                })
                ->addColumn('status', function ($row) {
                    return $row->finish ? '<span class="badge badge-success">Selesai</span>' : '<span class="badge badge-warning">Proses</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-primary btn-sm pilih-permintaan"
                    data-id="' .
                        $row->id .
                        '"
                    data-id_bahan="' .
                        $row->id_bahan .
                        '"
                    data-nama="' .
                        $row->bahan_nama .
                        '"
                    data-satuan="' .
                        $row->satuan .
                        '"
                    data-harga="' .
                        $row->harga .
                        '">
                    Pilih
                </button>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
    }

    public function getData(Request $request)
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $jenis = $request->input('jenis');

        $po_bahan = DB::table('inv_po')->join('suppliers', 'inv_po.id_suplier', '=', 'suppliers.id')->select('no_po', 'no_order', 'tanggal', 'nama', 'totalppn', 'totalexclude', 'totalinclude', 'status', 'untukperhatian', 'diskon', 'ongkir', 'inputlabel', 'term', 'kunci')->where('jenis', $jenis);
        if ($bulan != '0') {
            $po_bahan->whereMonth('tanggal', '=', $bulan);
        }
        if ($tahun) {
            $po_bahan->whereYear('tanggal', '=', $tahun);
        }
        $searchValue = $request->input('search.value');
        if (!empty($searchValue)) {
            $po_bahan->where(function ($query) use ($searchValue) {
                $query->where('no_po', 'like', "%{$searchValue}%")->orWhere('nama', 'like', "%{$searchValue}%");
            });
        }
        $recordsTotal = DB::table('inv_po')->where('jenis', $jenis)->whereMonth('tanggal', '=', $bulan)->whereYear('tanggal', '=', $tahun)->count();
        $recordsFiltered = $po_bahan->count();
        $length = $request->input('length');
        $start = $request->input('start');
        $draw = $request->input('draw');

        $po_bahan = $po_bahan->orderBy('inv_po.id', 'desc')->offset($start)->limit($length)->get();
        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $po_bahan,
        ]);
    }
    public function showDetail(Request $request)
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $jenis = $request->input('jenis');
        $searchValue = $request->input('search.value');
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $draw = $request->input('draw');
        $query = DB::table('inv_podetail')
            ->join('bahan', 'inv_podetail.id_bahan', '=', 'bahan.id')
            ->join('inv_po', 'inv_podetail.no_po', '=', 'inv_po.no_po')
            ->join('suppliers', 'inv_po.id_suplier', '=', 'suppliers.id')
            ->select(['inv_podetail.jumlah', 'inv_podetail.diterima', 'bahan.nama', 'bahan.keterangan_bahan', 'bahan.satuan', 'inv_po.no_po', 'inv_po.tanggal', 'inv_po.jenis', 'suppliers.nama as nama_supplier'])
            ->when($jenis !== null, function ($q) use ($jenis) {
                return $q->whereRaw('inv_po.jenis = ?', [$jenis]);
            })
            ->when($bulan != '0', function ($q) use ($bulan) {
                return $q->whereMonth('inv_po.tanggal', $bulan);
            })
            ->when($tahun, function ($q) use ($tahun) {
                return $q->whereYear('inv_po.tanggal', $tahun);
            });
        $recordsTotal = (clone $query)->count();
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->Where('bahan.nama', 'like', "%{$searchValue}%");
            });
        }
        $recordsFiltered = (clone $query)->count();
        $data = $query->orderBy('inv_podetail.id', 'desc')->offset($start)->limit($length)->get();
        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function getDatadetail(Request $request)
    {
        $jenis = $request->input('jenis');
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $title = $jenis == 1 ? 'Detail Pembelian PP' : ($jenis == 2 ? 'Non PO/PP' : 'Detail Pembelian PO');
        $data = [
            'title' => $title,
            'jenis' => $jenis,
            'bulan' => $bulan,
            'tahun' => $tahun,
        ];
        if ($request->ajax()) {
            $view = view('purchasing.po.formdetail', $data)->render();
            return response()->json(['data' => $view]);
        }
    }
    public function create(Request $request)
    {
        $jenis = $request->input('jenis');
        $title = $jenis == 1 ? ' Pembelian PP' : ($jenis == 2 ? 'Non PO/PP' : ' Pembelian PO');

        $data = [
            'title' => $title,
            'jenis' => $jenis,
        ];
        if ($request->ajax()) {
            $view = view('purchasing.po.formtambah', $data)->render();
            return response()->json(['data' => $view]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $edit = $request->input('edit');

        if ($edit == 0) {
            try {
                $request->validate([
                    'id_suplier' => 'required|integer',
                ]);
                $user = session('user_data');
                $userId = $user['id'];
                $tanggal = $request->input('tanggal');
                $jenis = $request->input('jenis');
                $id_suplier = $request->input('id_suplier');
                $no_order = $request->input('no_order');
                $untukperhatian = $request->input('untukperhatian');
                $term = $request->input('term');
                $notes = $request->input('notes');
                $dataFromTable2 = $request->input('data');
                $SumTotalExclude = $request->input('SumTotalExclude');
                $SumTotalppn = $request->input('SumTotalppn');
                $SumTotalInclude = $request->input('SumTotalInclude');
                $diskon = $request->input('diskon');
                $ongkir = $request->input('ongkir');
                $inputlabel = $request->input('inputlabel');
                $GrandTotalPembelian = $request->input('GrandTotalPembelian');

                $newPONumber = POModel::generatePONumber($tanggal, $jenis);
                $po = new POModel();
                $po->no_po = $newPONumber;
                $po->tanggal = $tanggal;
                $po->id_suplier = $id_suplier;
                $po->no_order = $no_order;
                $po->untukperhatian = $untukperhatian;
                $po->term = $term;
                $po->notes = $notes;
                $po->ppn = $SumTotalppn > 0 ? config('app.konstanta_ppn') : 0;
                $po->totalexclude = $SumTotalExclude;
                $po->totalppn = $SumTotalppn;
                $po->totalinclude = $SumTotalInclude;
                $po->diskon = $diskon;
                $po->ongkir = $ongkir;
                $po->GrandTotalPembelian = $GrandTotalPembelian;
                $po->user_id = $userId;
                $po->jenis = $jenis;
                $po->inputlabel = $inputlabel;
                $po->save();

                foreach ($dataFromTable2 as $item) {
                    $detail = new DetailPOModel();
                    $detail->no_po = $newPONumber;
                    $detail->id_bahan = $item['id_bahan'];
                    $detail->jumlah = $item['qty'];
                    $detail->harga = $item['harga'];
                    $detail->exclude = $item['sumhargaexcl'];
                    $detail->ppn = $item['nominalppn'];
                    $detail->include = $item['sumhargaincl'];
                    $detail->id_permintaan = $item['id_permintaan'];
                    $detail->jenis = $jenis;
                    $detail->save();
                }
                Permintaan::whereColumn('realisasi', '>=', 'jumlah_order')->update(['finish' => 1]);
                return response()->json(['message' => $newPONumber], 200);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
        } elseif ($edit == 2) {
            try {
                DB::beginTransaction();
                $no_po = $request->input('no_po');
                $jenis = $request->input('jenis');
                $po = POModel::findOrFail($no_po);

                if ($po && $po->cetak >= 1) {
                    DB::statement('CALL SP_ArchivePo(?)', [$po->no_po]);
                    $po->cetak_ulang = '1';
                }

                $po->tanggal = $request->input('tanggal');
                $po->id_suplier = $request->input('id_suplier');
                $po->no_order = $request->input('no_order');
                $po->untukperhatian = $request->input('untukperhatian');
                $po->term = $request->input('term');
                $po->notes = $request->input('notes');
                $po->totalexclude = $request->input('SumTotalExclude');
                $po->totalppn = $request->input('SumTotalppn');
                $po->totalinclude = $request->input('SumTotalInclude');
                $po->diskon = $request->input('diskon');
                $po->ongkir = $request->input('ongkir');
                $po->inputlabel = $request->input('inputlabel');
                $po->GrandTotalPembelian = $request->input('GrandTotalPembelian');
                $po->ppn = $request->input('SumTotalppn') > 0 ? config('app.konstanta_ppn') : 0;
                $po->jenis = $jenis;
                $po->save();

                $dataFromTable2 = $request->input('data');
                $keptBahanIds = [];

                foreach ($dataFromTable2 as $item) {
                    $id_bahan = $item['id_bahan'];
                    $keptBahanIds[] = $id_bahan;

                    DetailPOModel::updateOrCreate(
                        [
                            'no_po' => $no_po,
                            'id_bahan' => $id_bahan
                        ],
                        [
                            'jumlah' => $item['qty'],
                            'harga' => $item['harga'],
                            'exclude' => $item['sumhargaexcl'],
                            'ppn' => $item['nominalppn'],
                            'include' => $item['sumhargaincl'],
                            'id_permintaan' => $item['id_permintaan'] ?? 0,
                            'jenis' => $jenis
                        ]
                    );
                }

                DetailPOModel::where('no_po', $no_po)
                    ->whereNotIn('id_bahan', $keptBahanIds)
                    ->delete();

                Permintaan::whereColumn('realisasi', '>=', 'jumlah_order')->update(['finish' => 1]);

                DB::commit();
                return response()->json(['message' => $no_po], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 400);
            }
        } else {
            try {
                $id_bahan = $request->input('id_bahan');
                $no_po = $request->input('no_po');
                $harga = $request->input('harga');
                $jumlah = $request->input('jumlah');
                $id_permintaan = $request->input('id_permintaan', 0);
                $exclude = round($harga * $jumlah, 2);
                $po = POModel::findOrFail($no_po);

                if ($po && $po->cetak >= 1) {
                    DB::statement('CALL SP_ArchivePo(?)', [$po->no_po]);
                    $po->cetak_ulang = '1';
                }

                $ppn = $po->ppn;
                $nilaippn = round(($exclude * $ppn) / 100, 2);

                $detail = new DetailPOModel();
                $detail->no_po = $no_po;
                $detail->id_bahan = $id_bahan;
                $detail->jumlah = $jumlah;
                $detail->harga = $harga;
                $detail->exclude = $exclude;
                $detail->ppn = $nilaippn;
                $detail->include = $exclude + $nilaippn;
                $detail->id_permintaan = $id_permintaan;
                $detail->jenis = $po->jenis;
                $detail->save();

                $newexclude = $po->totalexclude + $exclude;
                $newbruto = $newexclude - $po->diskon;
                $newnilaippn = round(($newbruto * $ppn) / 100, 2);
                $newinclude = $newexclude + $newnilaippn;

                $po->totalexclude = $newexclude;
                $po->totalppn = round($newnilaippn, 2);
                $po->totalinclude = round($newinclude, 2);
                $po->GrandTotalPembelian = round($newexclude - $po->diskon + $newnilaippn + $po->ongkir, 2);
                $po->save();

                Permintaan::whereColumn('realisasi', '>=', 'jumlah_order')->update(['finish' => 1]);
                return response()->json(['message' => $no_po], 200);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $no_po)
    {
        $po_bahan_detail = DB::table('inv_podetail')->join('bahan', 'inv_podetail.id_bahan', '=', 'bahan.id')->where('no_po', $no_po)->select('inv_podetail.id as unique', 'id_bahan', 'jumlah', 'inv_podetail.harga as harga_bahan', 'exclude', 'ppn', 'include', 'nama', 'satuan')->get();

        return response()->json($po_bahan_detail);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $noPo)
    {
        // Menggunakan fungsi statis detail() agar mendapatkan join ke tabel bahan
        $details = DetailPOModel::detail($noPo);

        $po = POModel::with('supplier')->findOrFail($noPo);

        $data = [
            'title'   => 'Edit / Tambah Detail PO',
            'noPo'    => $noPo,
            'po'      => $po,
            'details' => $details,
            'jenis'   => $request->input('jenis'),
        ];

        $view = view('purchasing.po.tambahkekurangan', $data)->render();
        return response()->json(['data' => $view]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $no_po)
    {
        $closestatus = $request->input('closestatus');
        $term_pembayaran = $request->input('term_pembayaran');
        if ($closestatus == 0) {
            $validatedData = $request->validate([
                'no_order' => 'required|string|max:255',
            ]);

            try {
                $podata = POModel::findOrFail($no_po);
                $podata->no_order = $validatedData['no_order'];
                $podata->term = $term_pembayaran;
                $podata->save();
                return response()->json([
                    'success' => true,
                    'message' => 'Data berhasil diperbarui',
                    'data' => $podata,
                ]);
            } catch (\Exception $e) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage(),
                    ],
                    500,
                );
            }
        } elseif ($closestatus == 99) {
            $diskonedit = $request->input('diskonedit');
            $ongkiredit = $request->input('ongkiredit');
            $inputlabeledit = $request->input('inputlabeledit');

            try {
                $pokertas = POModel::findOrFail($no_po);
                if ($pokertas && $pokertas->cetak >= 1) {
                    DB::statement('CALL SP_ArchivePo(?)', [$pokertas->no_po]);
                    $pokertas->cetak_ulang = '1';
                }
                if ($pokertas->kunci != 0) {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => 'Data tidak bisa diperbarui karena sudah dikunci.',
                        ],
                        403,
                    );
                }
                $exclude = $pokertas->totalexclude;
                $excludeminusdiskon = $exclude - $diskonedit;
                $acuanppn = $pokertas->ppn;
                $newtotalppn = round(($excludeminusdiskon * $acuanppn) / 100, 2);
                $newinclude = $excludeminusdiskon + $newtotalppn;

                $pokertas->totalppn = $newtotalppn;
                $pokertas->totalinclude = $newinclude;
                $pokertas->diskon = $diskonedit;
                $pokertas->ongkir = $ongkiredit;
                $pokertas->GrandTotalPembelian = $newinclude + $ongkiredit;
                $pokertas->inputlabel = $inputlabeledit;

                $pokertas->save();
                return response()->json([
                    'success' => true,
                    'message' => 'Data berhasil diperbarui',
                    'data' => $pokertas,
                ]);
            } catch (\Exception $e) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage(),
                    ],
                    500,
                );
            }
        } else {
            try {
                $pokertas = POModel::findOrFail($no_po);
                if ($pokertas->status != '2') {
                    $pokertas->status = $closestatus;
                    $pokertas->save();
                    $details = DetailPOModel::where('no_po', $no_po)->whereColumn('jumlah', '>', 'diterima')->get();

                    foreach ($details as $detail) {
                        $selisih = $detail->jumlah - $detail->diterima;
                        Bahan::where('id', $detail->id_bahan)->decrement('stok_onpurchase', $selisih);
                    }
                    return response()->json([
                        'success' => true,
                        'message' => 'Data berhasil diperbarui',
                        'data' => $pokertas,
                    ]);
                } else {
                    return response()->json(
                        [
                            'success' => false,
                            'message' => 'PO sudah diclose sebelumnya ',
                        ],
                        500,
                    );
                }
            } catch (\Exception $e) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage(),
                    ],
                    500,
                );
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $detail = DetailPOModel::find($id);
            if (!$detail) {
                return response()->json(['message' => 'Detail tidak ditemukan'], 404);
            }
            $pokertas = POModel::where('no_po', $detail->no_po)->first();
            if (!$pokertas) {
                return response()->json(['message' => 'Header PO tidak ditemukan'], 404);
            }
            $jumlahDetail = DetailPOModel::where('no_po', $detail->no_po)->count();
            if ($jumlahDetail <= 1) {
                $detail->delete();
                $pokertas->delete();
                $message = 'Detail terakhir dihapus, Header PO otomatis ikut terhapus.';
            } else {
                $newtotalexclude = $pokertas->totalexclude - $detail->exclude;
                $newtotalppn = round((($newtotalexclude - $pokertas->diskon) * $pokertas->ppn) / 100, 2);
                $newtotalinclude = $newtotalexclude + $newtotalppn;
                $pokertas->update([
                    'totalppn' => $newtotalppn,
                    'totalexclude' => $newtotalexclude,
                    'totalinclude' => $newtotalinclude,
                    'GrandTotalPembelian' => $newtotalinclude - $pokertas->diskon + $pokertas->ongkir,
                ]);
                $detail->delete();
                $message = 'Detail berhasil dihapus dan saldo PO diperbarui.';
            }
            DB::commit();
            return response()->json(['message' => $message], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal: ' . $e->getMessage()], 500);
        }
    }

    public function cetakpembelian(Request $request)
    {
        set_time_limit(300);

        $nomorpo = $request->input('nomorpo');
        $header = POModel::headernomorpo($nomorpo);
        $detailsx = DetailPOModel::detail($nomorpo);
        $no_order = $header->no_order != '-' ? $header->no_order : '';
        $up = $header->untukperhatian;
        $tanggalFormatted = date('d F Y', strtotime($header->tanggal));

        $data = [
            'title' => 'PT.MULIAOFFSET PACKINDO',
        ];
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->SetMargins(4, 4, 5);

        if ($header->jenis == 1) {
            $pdf->SetTitle('PEMBELIAN BAHAN PENOLONG (PP)');
        } else {
            $pdf->SetTitle('PEMBELIAN BAHAN PENUNJANG (PO)');
        }
        $pdf->AddPage();

        $imagePath = public_path('img/logomuliaoffset.png');
        $logox = public_path('img/logofullnew_lokal.png');

        $pageWidth = $pdf->getPageWidth();
        $margin = 8;
        $logoWidth = 12;
        $logoRightWidth = 25;

        $pdf->Image(public_path('img/logomuliaoffset.png'), 5, 5, $logoWidth);

        $pdf->Image(public_path('img/logofullnew_lokal.png'), $pageWidth - $margin - $logoRightWidth, 3, $logoRightWidth);

        $html =
            '
        <table>
            <tr>
                <td width="385px" align="center"><h1><i>' .
            $data['title'] .
            '</i></h1></td>
            </tr>
            <tr>
                <td style="font-size: 0.575em" align="center">
                    Madukoro Blok B No.15, Semarang 50144 Telp:(024)7603141-43 Fax:(024)7603133
                    <br>Homepage:www.muliaprintinggroup.com e-mail:muliaoffset@muliaprintinggroup.com
                </td>
            </tr>
        </table>
        <hr>
        <table>
            <tr>
                <td colspan="2" align="center" style="font-weight: bold; font-size: 1em;">PURCHASE ORDER</td>
            </tr>
            <tr>
                <td colspan="2" align="center" style="font-weight: bold; font-size: 0.8em;">' .
            $header->no_po .
            '</td>
            </tr>
        </table>

        <table cellpadding="3">
            <tr>
                <td align="left" style="font-weight: bold; font-size: 0.70em; padding-top: 10px;">Kepada Yth,</td>
                <td align="right" style="font-weight: bold; font-size: 0.70em; padding-top: 10px;">' .
            $no_order .
            '</td>
            </tr>
            <tr>
                <td align="left" style="font-weight: bold; font-size: 0.70em;">' .
            strtoupper($header->nama) .
            '</td>
                <td align="right" style="font-weight: bold; font-size: 0.70em;">Term : ' .
            $header->term .
            '</td>
            </tr>
            <tr>
                <td colspan="2" align="left" style="font-weight: bold; font-size: 0.70em; padding-top: 10px;">UP : ' .
            strtoupper($up) .
            '</td>
            </tr>
        </table>

        <table cellpadding="2">
            <tr>
                <td align="left" style="font-weight: bold; font-size: 0.70em;">Dengan Hormat</td>
            </tr>
            <tr>
                <td align="left" style="font-weight: bold; font-size: 0.70em;">Bersama dengan ini kami sampaikan purchase order dengan rincian sebagai berikut :</td>
            </tr>
        </table>

        <table cellpadding="3" style="font-size: 0.780em; table-layout: fixed; margin-left: 20px; margin-right: 20px; border-collapse: collapse; width: 100%;">
            <tr class="text-center">
                <td width="20px" align="center" style="border: 1px solid black;">No</td>
                <td width="250px" align="center" style="border: 1px solid black;">Nama</td>
                <td width="70px" align="center" style="border: 1px solid black;">QTY</td>
                <td width="70px" align="center" style="border: 1px solid black;">Satuan</td>
                <td width="75px" align="center" style="border: 1px solid black;">Harga</td>
                <td width="90px" align="center" style="border: 1px solid black;">Jumlah Harga</td>
            </tr>
            <tbody style="overflow: auto;">';

        $baris = 1;
        foreach ($detailsx as $detail) {
            $html .=
                '<tr>
                <td width="20px" align="center" style="border: 1px solid black;">' .
                $baris++ .
                '</td>
                <td width="250px" align="left" style="border: 1px solid black;">' .
                $detail->nama .
                (!empty($detail->keterangan_bahan) ? '<br>' . nl2br(htmlspecialchars($detail->keterangan_bahan)) : '') .
                '</td>
                <td width="70px" align="center" style="border: 1px solid black;">' .
                number_format($detail->jumlah, 2, ',', '.') .
                '</td>
                <td width="70px" align="left" style="border: 1px solid black;">' .
                $detail->satuan .
                '</td>
                <td width="75px" align="right" style="border: 1px solid black;">' .
                number_format($detail->harga, 2, ',', '.') .
                '</td>
                <td width="90px" align="right" style="border: 1px solid black;">' .
                number_format($detail->exclude, 2, ',', '.') .
                '</td>
            </tr>';
        }

        $html .=
            '<tr>
        <td colspan="5" align="right" style="border: 1px solid black;">Total : </td>
        <td width="90px" align="right" style="border: 1px solid black;">' .
            number_format($header->totalexclude, 2, ',', '.') .
            '</td>
        </tr>
        <tr>
            <td width="35px">Note : </td>
            <td rowspan="4" colspan="3" width="305px" align="left">' .
            nl2br(htmlspecialchars($header->notes)) .
            '</td>
            <td colspan="2" width="145px" align="right" style="border: 1px solid black;">Diskon </td>
            <td width="90px" align="right" style="border: 1px solid black;">' .
            number_format($header->diskon, 2, ',', '.') .
            '</td>
        </tr>
        <tr>
            <td width="35px"></td>
            <td width="145px" align="right" style="border: 1px solid black;">PPN </td>
            <td width="90px" align="right" style="border: 1px solid black;">' .
            number_format($header->totalppn, 2, ',', '.') .
            '</td>
        </tr>
        <tr>
            <td width="35px"></td>
            <td width="145px" align="right" style="border: 1px solid black;">' .
            ($header->inputlabel == '-' ? 'Freight Handling' : $header->inputlabel) .
            '</td>
            <td width="90px" align="right" style="border: 1px solid black;">' .
            number_format($header->ongkir, 2, ',', '.') .
            '</td>
        </tr>
        <tr>
            <td width="35px"></td>
            <td width="145px" align="right" style="border: 1px solid black;">Total Order </td>
            <td width="90px" align="right" style="border: 1px solid black;"><b>' .
            number_format($header->GrandTotalPembelian, 2, ',', '.') .
            '</b></td>
        </tr>
        </tbody>
        </table><br>';

        $pdf->setPrintFooter(false);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->SetAutoPageBreak(false, PDF_MARGIN_BOTTOM);

        $fhtml =
            '
        <table>
            <tr>
                <td colspan="3" align="left" style="font-weight: bold; font-size: 0.70em;">Demikian purchase order ini kami sampaikan, atas perhatiannya kami ucapkan terima kasih.</td>
            </tr>
            <tr>
                <td></td><td></td>
                <td align="center" style="font-weight: bold; font-size: 0.8em;">Semarang ,' .
            $tanggalFormatted .
            '</td>
            </tr>
            <tr>
                <td></td><td></td>
                <td align="center" style="font-weight: bold; font-size: 0.70em;">Hormat Kami</td>
            </tr>
            <tr><td></td><td></td><td align="center" style="height: 50px;"></td></tr>
            
            <tr><td></td><td></td><td align="center" style="font-weight: bold; font-size: 0.70em;"> Roy Mulyono </td></tr>
        </table>';
        $pdf->writeHTML($fhtml, true, false, true, false, '');
        $pdf->SetAutoPageBreak(false, PDF_MARGIN_BOTTOM);
        $pdf->SetXY(0, -15);

        $tanggal_cetak = date('d-m-Y H:i');
        if ($header->cetak == 1) {
            $footer_text = "Cetakan asli $header->counter_asli, dicetak $tanggal_cetak";
        } else {
            $cetakanke = $header->cetak - 1;
            $footer_text = "Cetakan duplikat ke-$cetakanke dari asli $header->counter_asli, dicetak $tanggal_cetak";
        }

        $footerhtml =
            '
        <table width=100% cellpadding="2"><tr><td align="center" style="font-size: 0.750em">' .
            $footer_text .
            '</td></tr></table>';
        $pdf->writeHTML($footerhtml, true, false, true, false, '');

        ob_end_clean();
        $pdf->Output('nama_file.pdf', 'I');
    }

    public function invoiceLpb()
    {
        $user = session('user_data');
        if ($user && isset($user['type']) && $user['type'] == 5) {
            return view('purchasing.invoicelpb', compact('user'));
        }
        abort(403, 'ANDA TIDAK MEMILIKI AKSES KE HALAMAN INI.');
    }
    public function lpbreturn()
    {
        $user = session('user_data');
        if ($user && isset($user['type']) && $user['type'] == 5) {
            return view('purchasing.lpbreturn', compact('user'));
        }
        abort(403, 'ANDA TIDAK MEMILIKI AKSES KE HALAMAN INI.');
    }

    public function getInvoiceData(Request $request): JsonResponse
    {
        if ($request->boolean('return')) {
            $query = DB::table('admin_lpb_return as alr')
                ->leftJoin('admin_lpb_detail_return as aldr', 'alr.id_return', '=', 'aldr.id_return')
                ->join('inv_po', 'alr.no_po', '=', 'inv_po.no_po')
                ->join('suppliers', 'inv_po.id_suplier', '=', 'suppliers.id')
                ->select('alr.id', 'alr.id_return as id_return_col', 'alr.no_invoice', 'alr.tanggal', 'alr.no_po', 'suppliers.nama as supplier_nama', 'alr.jenis_lpb', DB::raw('SUM(aldr.jumlah_barang_diterima * aldr.harga) as grand_total'))
                ->groupBy('alr.id', 'alr.id_return', 'alr.no_invoice', 'alr.tanggal', 'alr.no_po', 'suppliers.nama', 'alr.jenis_lpb');

            if ($request->filled('periode')) {
                try {
                    $periode = Carbon::parse($request->periode);
                    $query->whereYear('alr.tanggal', $periode->year)->whereMonth('alr.tanggal', $periode->month);
                } catch (\Exception $e) {
                }
            }

            if ($request->filled('search.value')) {
                $searchValue = $request->input('search.value');
                $query->where(function ($q) use ($searchValue) {
                    $q->where('alr.id_return', 'like', '%' . $searchValue . '%')
                        ->orWhere('suppliers.nama', 'like', '%' . $searchValue . '%')
                        ->orWhere('alr.no_po', 'like', '%' . $searchValue . '%');
                });
            }
        } else {
            $query = DB::table('invoice_lpb')
                ->leftJoin('suppliers', 'invoice_lpb.kode_supplier', '=', 'suppliers.id')
                ->leftJoin('admin_lpb', 'invoice_lpb.no_invoice', '=', 'admin_lpb.no_invoice')
                ->leftJoin('inv_jasa', 'invoice_lpb.no_invoice', '=', 'inv_jasa.no_jasa')
                ->select(
                    'invoice_lpb.id',
                    'invoice_lpb.no_invoice',
                    'invoice_lpb.tanggal',
                    'invoice_lpb.tgl_deadline_pembayaran',
                    'invoice_lpb.grand_total',
                    'invoice_lpb.status_pembayaran',
                    'invoice_lpb.sub_total',
                    'invoice_lpb.ppn',
                    'invoice_lpb.diskon',
                    'invoice_lpb.pph',
                    'invoice_lpb.ongkir',
                    'invoice_lpb.total_pembayaran',
                    'invoice_lpb.sisa_tagihan',
                    'invoice_lpb.note',
                    DB::raw('COALESCE(suppliers.nama, inv_jasa.nama) as supplier_nama'),
                    DB::raw('COALESCE(GROUP_CONCAT(DISTINCT admin_lpb.no_po), inv_jasa.no_jasa) as no_po'),
                    DB::raw('COALESCE(MIN(admin_lpb.jenis_lpb), 3) as jenis_lpb')
                )
                ->groupBy(
                    'invoice_lpb.id',
                    'invoice_lpb.no_invoice',
                    'invoice_lpb.tanggal',
                    'invoice_lpb.tgl_deadline_pembayaran',
                    'invoice_lpb.grand_total',
                    'invoice_lpb.status_pembayaran',
                    'invoice_lpb.sub_total',
                    'invoice_lpb.ppn',
                    'invoice_lpb.diskon',
                    'invoice_lpb.pph',
                    'invoice_lpb.ongkir',
                    'invoice_lpb.total_pembayaran',
                    'invoice_lpb.sisa_tagihan',
                    'invoice_lpb.note',
                    'suppliers.nama',
                    'inv_jasa.nama',
                    'inv_jasa.no_jasa'
                );

            if ($request->filled('periode')) {
                try {
                    $periode = Carbon::parse($request->periode);
                    $query->whereYear('invoice_lpb.tanggal', $periode->year)->whereMonth('invoice_lpb.tanggal', $periode->month);
                } catch (\Exception $e) {
                }
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $status = $request->status;
                if ($status == '3') {
                    $query->where('invoice_lpb.tgl_deadline_pembayaran', '<', Carbon::today())->where('invoice_lpb.status_pembayaran', '!=', 'Lunas');
                } else {
                    $statusMapping = ['0' => ['Siap Bayar', 'Belum Dibayar'], '1' => ['Proses Pembayaran', 'Proses'], '2' => ['Lunas']];
                    if (array_key_exists($status, $statusMapping)) {
                        $query->whereIn('invoice_lpb.status_pembayaran', $statusMapping[$status]);
                    }
                }
            }

            if ($request->filled('search.value')) {
                $searchValue = $request->input('search.value');
                $query->where(function ($q) use ($searchValue) {
                    $q->where('invoice_lpb.no_invoice', 'like', '%' . $searchValue . '%')
                        ->orWhere('suppliers.nama', 'like', '%' . $searchValue . '%')
                        ->orWhere('inv_jasa.nama', 'like', '%' . $searchValue . '%')
                        ->orWhere('admin_lpb.no_po', 'like', '%' . $searchValue . '%')
                        ->orWhere('inv_jasa.no_jasa', 'like', '%' . $searchValue . '%');
                });
            }
        }

        $recordsFiltered = DB::query()->fromSub($query, 'sub')->count();

        $defaultOrderColumn = $request->boolean('return') ? 'alr.id' : 'invoice_lpb.tanggal';
        $defaultOrderDirection = 'desc';

        $orderableColumns = ['no_invoice', 'tanggal', 'no_po', 'supplier_nama', 'tgl_deadline_pembayaran', 'id_return_col', 'alr.id'];
        $requestedColumnIndex = $request->input('order.0.column');
        $requestedColumnName = $request->input("columns.{$requestedColumnIndex}.name");

        $orderColumn = in_array($requestedColumnName, $orderableColumns) ? $requestedColumnName : $defaultOrderColumn;
        $orderDirection = $request->input('order.0.dir') ?? $defaultOrderDirection;

        $query->orderBy($orderColumn, $orderDirection);

        $data = $query->skip($request->input('start'))->take($request->input('length'))->get();

        if (!$request->boolean('return')) {
            $data->each(function ($item) {
                $item->is_overdue = Carbon::now()
                    ->startOfDay()
                    ->gt(Carbon::parse($item->tgl_deadline_pembayaran));
            });
        }

        $recordsTotal = $request->boolean('return') ? DB::table('admin_lpb_return')->count() : DB::table('invoice_lpb')->count();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function getAvailablePurchaseOrders(Request $request): JsonResponse
    {
        $query = DB::table('admin_lpb as al')->join('inv_po as ip', 'al.no_po', '=', 'ip.no_po')->join('suppliers as s', 'ip.id_suplier', '=', 's.id')->select('al.no_po', 'ip.id_suplier as kode_supplier', 's.nama as nama_supplier', 'ip.ppn')->where('al.status', 0)->where('al.flag', 0);

        $purchaseOrders = $query->distinct()->orderBy('s.nama', 'asc')->orderBy('al.no_po', 'asc')->get();

        return response()->json($purchaseOrders);
    }

    public function getLpbByPo(Request $request, $no_po): JsonResponse
    {
        try {
            $lpbHeaders = DB::table('admin_lpb as al')->join('inv_po as ip', 'al.no_po', '=', 'ip.no_po')->join('suppliers as s', 'ip.id_suplier', '=', 's.id')->select('al.id_lpb', 'al.tanggal as tgl_lpb', 's.nama as nama_supplier', 'al.status')->where('al.no_po', $no_po)->where('al.status', 0)->where('al.flag', 0)->get();

            if ($lpbHeaders->isEmpty()) {
                return response()->json(['data' => []]);
            }

            $lpbIds = $lpbHeaders->pluck('id_lpb');

            $lpbDetails = DB::table('admin_lpb_detail as ald')
                ->join('bahan', 'ald.id_bahan', '=', 'bahan.id')
                ->leftJoin('inv_podetail as ipd', function ($join) use ($no_po) {
                    $join->on('ipd.id_bahan', '=', 'ald.id_bahan')->where('ipd.no_po', '=', $no_po);
                })
                ->select('ald.id', 'ald.id_lpb', 'ald.id_bahan', 'bahan.nama as nama_bahan', 'ald.jumlah_barang_diterima', 'ald.lot_number', 'ald.harga as harga_lpb_asli', DB::raw('COALESCE(ipd.harga, ald.harga) as harga_final'), DB::raw('ald.jumlah_barang_diterima * COALESCE(ipd.harga, ald.harga) as sub_total_item'))
                ->whereIn('ald.id_lpb', $lpbIds)
                ->get()
                ->groupBy('id_lpb');

            $result = $lpbHeaders->map(function ($header) use ($lpbDetails) {
                $detailsForHeader = $lpbDetails->get($header->id_lpb, collect());
                $header->total_qty = $detailsForHeader->sum('jumlah_barang_diterima');
                $header->sub_total_lpb = $detailsForHeader->sum('sub_total_item');
                $header->details = $detailsForHeader;

                return $header;
            });

            return response()->json(['data' => $result]);
        } catch (\Exception $e) {
            Log::error('Error fetching LPB data with details: ' . $e->getMessage());
            return response()->json(['data' => [], 'error' => 'Gagal mengambil data LPB.'], 500);
        }
    }

    public function storeInvoice(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'no_invoice' => 'required|string|max:100|unique:invoice_lpb,no_invoice',
                'kode_supplier' => 'required|string|max:50|exists:suppliers,id',
                'tanggal_nota' => 'required|date_format:Y-m-d',
                'tgl_deadline_pembayaran' => 'required|date_format:Y-m-d|after_or_equal:tanggal_nota',
                'sub_total' => 'required|string',
                'ppn' => 'required|string',
                'diskon_nominal' => 'required|string',
                'pph_operator' => 'required|in:+,-',
                'ongkir' => 'required|string',
                'grand_total' => 'required|string',
                'selected_lpb_ids' => 'required|string',
                'note' => 'nullable|string|max:1000',
            ],
            [
                'no_invoice.unique' => 'Nomor invoice ini sudah pernah digunakan.',
                'kode_supplier.exists' => 'Kode supplier tidak valid atau tidak ditemukan.',
                'selected_lpb_ids.required' => 'Minimal satu LPB harus dipilih.',
            ],
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $pph_operator = $request->input('pph_operator');
            $pph_value = $this->parseCurrency($request->input('pph'));
            $adjustedPph = $pph_operator === '-' ? -abs($pph_value) : abs($pph_value);
            $invoiceData = [
                'no_invoice' => $request->input('no_invoice'),
                'kode_supplier' => $request->input('kode_supplier'),
                'tanggal' => $request->input('tanggal_nota'),
                'tgl_deadline_pembayaran' => $request->input('tgl_deadline_pembayaran'),
                'sub_total' => round($this->parseCurrency($request->input('sub_total')), 2),
                'ppn' => round($this->parseCurrency($request->input('ppn')), 2),
                'diskon' => round($this->parseCurrency($request->input('diskon_nominal')), 2),
                'pph' => round($adjustedPph, 2),
                'ongkir' => round($this->parseCurrency($request->input('ongkir')), 2),
                'grand_total' => round($this->parseCurrency($request->input('grand_total')), 2),
                'note' => $request->input('note'),
                'status_pembayaran' => 'Belum Dibayar',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $invoiceId = DB::table('invoice_lpb')->insertGetId($invoiceData);

            $selectedLpbIds = array_filter(explode(',', $request->input('selected_lpb_ids')));

            DB::table('admin_lpb')
                ->whereIn('id_lpb', $selectedLpbIds)
                ->update([
                    'no_invoice' => $request->input('no_invoice'),
                    'status' => 1,
                ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Invoice berhasil disimpan!', 'invoice_id' => $invoiceId], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing invoice: ' . $e->getMessage(), ['request' => $request->all()]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan internal saat menyimpan invoice.'], 500);
        }
    }

    public function getInvoiceDetail(Request $request, $id)
    {
        try {
            if ($request->boolean('return')) {
                $returnHeader = DB::table('admin_lpb_return')->where('id', $id)->first();
                if (!$returnHeader) {
                    return response()->json(['error' => 'Data return tidak ditemukan'], 404);
                }
                $items = DB::table('admin_lpb_detail_return as aldr')->join('bahan', 'aldr.id_bahan', '=', 'bahan.id')->where('aldr.id_return', $returnHeader->id_return)->select('aldr.id_lpb', 'bahan.nama as nama_bahan', 'aldr.jumlah_barang_diterima', 'aldr.harga', DB::raw('aldr.jumlah_barang_diterima * aldr.harga as sub_total_item'))->get();
                $totalReturnValue = $items->sum('sub_total_item');
                $returnPpn = 0;
                $returnDiscount = 0;
                $originalInvoice = DB::table('invoice_lpb')->where('no_invoice', $returnHeader->no_invoice)->first();

                if ($originalInvoice) {
                    if ($originalInvoice->ppn > 0) {
                        $ppnRate = config('app.konstanta_ppn', 11) / 100;
                        $returnPpn = $totalReturnValue * $ppnRate;
                    }
                    if ($originalInvoice->diskon > 0 && $originalInvoice->sub_total > 0) {
                        $discountRate = $originalInvoice->diskon / $originalInvoice->sub_total;
                        $returnDiscount = $totalReturnValue * $discountRate;
                    }
                }
                $financials = (object) [
                    'sub_total' => $totalReturnValue,
                    'ppn' => $returnPpn,
                    'diskon' => $returnDiscount,
                    'pph' => 0,
                    'ongkir' => 0,
                    'grand_total' => $totalReturnValue + $returnPpn - $returnDiscount,
                ];
                $itemsGrouped = $items->groupBy('id_lpb');

                return response()->json([
                    'financials' => $financials,
                    'items' => $itemsGrouped,
                ]);
            } else {
                $invoice = DB::table('invoice_lpb')->where('id', $id)->first();
                if (!$invoice) {
                    return response()->json(['error' => 'Invoice tidak ditemukan'], 404);
                }

                $linkedLpbs = DB::table('admin_lpb')
                    ->where('no_invoice', $invoice->no_invoice)
                    ->get(['id_lpb', 'no_po']);
                if ($linkedLpbs->isEmpty()) {
                    return response()->json([
                        'financials' => $invoice,
                        'items' => [],
                    ]);
                }

                $lpbIds = $linkedLpbs->pluck('id_lpb');
                $items = DB::table('admin_lpb_detail as ald')->join('bahan', 'ald.id_bahan', '=', 'bahan.id')->join('admin_lpb', 'ald.id_lpb', '=', 'admin_lpb.id_lpb')->select('admin_lpb.id_lpb', 'ald.id_bahan', 'bahan.nama as nama_bahan', 'ald.jumlah_barang_diterima', 'ald.harga', DB::raw('ald.jumlah_barang_diterima * ald.harga as sub_total_item'))->whereIn('ald.id_lpb', $lpbIds)->get();

                $itemsGrouped = $items->groupBy('id_lpb');

                return response()->json([
                    'financials' => $invoice,
                    'items' => $itemsGrouped,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error fetching invoice/return detail: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengambil rincian.'], 500);
        }
    }

    private function parseCurrency($value)
    {
        if (empty($value)) {
            return 0;
        }
        $cleanedValue = preg_replace('/[^\d,]/', '', $value);
        $floatValue = str_replace(',', '.', $cleanedValue);
        return (float) $floatValue;
    }

    public function destroyInvoice($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $invoice = DB::table('invoice_lpb')->where('id', $id)->first();

            if (!$invoice) {
                return response()->json(['success' => false, 'message' => 'Invoice tidak ditemukan.'], 404);
            }

            $protectedStatuses = ['Dibayar Sebagian', 'Proses Pembayaran', 'Proses', 'Lunas'];

            if (in_array($invoice->status_pembayaran, $protectedStatuses)) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Invoice ini sudah diproses bayar, data tidak dapat dihapus. Jika ingin menghapus, harap hubungi IT.',
                    ],
                    403,
                );
            }

            DB::table('admin_lpb')
                ->where('no_invoice', $invoice->no_invoice)
                ->update([
                    'status' => 0,
                    'flag' => 0,
                    'no_invoice' => null,
                ]);

            DB::table('invoice_lpb')->where('id', $id)->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Invoice berhasil dihapus.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menghapus invoice: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan pada server saat menghapus invoice.'], 500);
        }
    }

    public function getInvoiceItems($invoiceId)
    {
        try {
            $invoice = DB::table('invoice_lpb')->where('id', $invoiceId)->first();
            if (!$invoice) {
                return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
            }

            $lpbIds = DB::table('admin_lpb')->where('no_invoice', $invoice->no_invoice)->pluck('id_lpb');

            $items = DB::table('admin_lpb_detail as ald')->join('bahan', 'ald.id_bahan', '=', 'bahan.id')->whereIn('ald.id_lpb', $lpbIds)->select('ald.id_lpb', 'ald.id_bahan', 'bahan.nama as nama_bahan', 'ald.jumlah_barang_diterima', 'ald.harga')->orderBy('bahan.nama')->get();

            return response()->json($items);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data item.'], 500);
        }
    }

    public function updateInvoiceItems(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|integer|exists:invoice_lpb,id',
            'items' => 'required|array',
            'items.*.id_lpb' => 'required|string',
            'items.*.id_bahan' => 'required|integer',
            'items.*.new_quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data yang dikirim tidak lengkap atau tidak valid.', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user = session('user_data');
            $userId = $user['id'] ?? Auth::id();
            $itemsToReturn = $request->input('items');
            $createdReturnHeaders = [];

            foreach ($itemsToReturn as $item) {
                $returnQuantity = (float) $item['new_quantity'];

                if ($returnQuantity <= 0) {
                    continue;
                }

                $id_lpb = $item['id_lpb'];

                if (!isset($createdReturnHeaders[$id_lpb])) {
                    $originalLpbHeader = DB::table('admin_lpb')->where('id_lpb', $id_lpb)->first();
                    if (!$originalLpbHeader) {
                        throw new \Exception("Header LPB asli dengan ID {$id_lpb} tidak ditemukan.");
                    }

                    $todayPrefix = 'RT' . now()->format('md');
                    $lastReturn = DB::table('admin_lpb_return')
                        ->where('id_return', 'LIKE', $todayPrefix . '%')
                        ->orderBy('id_return', 'desc')
                        ->first();

                    $nextIncrement = 1;
                    if ($lastReturn) {
                        $lastNumber = (int) substr($lastReturn->id_return, 6);
                        $nextIncrement = $lastNumber + 1;
                    }
                    $incrementPadded = str_pad($nextIncrement, 3, '0', STR_PAD_LEFT);
                    $newReturnId = $todayPrefix . $incrementPadded;

                    DB::table('admin_lpb_return')->insert([
                        'id_return' => $newReturnId,
                        'id_lpb' => $originalLpbHeader->id_lpb,
                        'tanggal' => now(),
                        'no_po' => $originalLpbHeader->no_po,
                        'no_sj' => $originalLpbHeader->no_sj,
                        'id_user' => $userId,
                        'no_invoice' => $originalLpbHeader->no_invoice,
                        'status' => 0,
                        'flag' => 0,
                        'jenis_lpb' => $originalLpbHeader->jenis_lpb,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $createdReturnHeaders[$id_lpb] = $newReturnId;
                }

                $originalLpbDetail = DB::table('admin_lpb_detail')->where('id_lpb', $id_lpb)->where('id_bahan', $item['id_bahan'])->first();

                if (!$originalLpbDetail) {
                    throw new \Exception("Detail barang dengan ID Bahan {$item['id_bahan']} tidak ditemukan di LPB {$id_lpb}.");
                }

                DB::table('admin_lpb_detail_return')->insert([
                    'id_return' => $createdReturnHeaders[$id_lpb],
                    'id_lpb' => $id_lpb,
                    'id_bahan' => $item['id_bahan'],
                    'id_kategori' => $originalLpbDetail->id_kategori,
                    'jumlah_barang_diterima' => $returnQuantity,
                    'lot_number' => $originalLpbDetail->lot_number,
                    'harga' => $originalLpbDetail->harga,
                    'flag_dipakai' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Pencatatan barang bermasalah berhasil disimpan!']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mencatat LPB Return: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getAvailableJasa(Request $request): JsonResponse
    {
        $jasa = DB::table('inv_jasa')
            ->select('no_jasa', 'nama as nama_pelanggan', 'tanggal', 'ppn', 'totalexclude', 'totalppn', 'diskon', 'ongkir', 'GrandTotalPembelian')
            ->where('status', 0)
            ->orderBy('tanggal', 'desc')
            ->get();

        return response()->json($jasa);
    }

    public function storeInvoiceJasa(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'no_invoice' => 'required|string|max:100|unique:invoice_lpb,no_invoice',
            'tanggal_nota' => 'required|date_format:Y-m-d',
            'tgl_deadline_pembayaran' => 'required|date_format:Y-m-d|after_or_equal:tanggal_nota',
            'sub_total' => 'required|string',
            'ppn' => 'required|string',
            'diskon_nominal' => 'required|string',
            'pph_operator' => 'required|in:+,-',
            'ongkir' => 'required|string',
            'grand_total' => 'required|string',
            'selected_jasa_no' => 'required|string',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $pph_operator = $request->input('pph_operator');
            $pph_value = $this->parseCurrency($request->input('pph'));
            $adjustedPph = $pph_operator === '-' ? -abs($pph_value) : abs($pph_value);

            $invoiceData = [
                'no_invoice' => $request->input('no_invoice'),
                'kode_supplier' => null,
                'tanggal' => $request->input('tanggal_nota'),
                'tgl_deadline_pembayaran' => $request->input('tgl_deadline_pembayaran'),
                'sub_total' => round($this->parseCurrency($request->input('sub_total')), 2),
                'ppn' => round($this->parseCurrency($request->input('ppn')), 2),
                'diskon' => round($this->parseCurrency($request->input('diskon_nominal')), 2),
                'pph' => round($adjustedPph, 2),
                'ongkir' => round($this->parseCurrency($request->input('ongkir')), 2),
                'grand_total' => round($this->parseCurrency($request->input('grand_total')), 2),
                'note' => $request->input('note'),
                'status_pembayaran' => 'Belum Dibayar',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $invoiceId = DB::table('invoice_lpb')->insertGetId($invoiceData);

            DB::table('inv_jasa')
                ->where('no_jasa', $request->input('selected_jasa_no'))
                ->update([
                    'status' => 1,
                    'updated_at' => now()
                ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Invoice Jasa berhasil disimpan!', 'invoice_id' => $invoiceId], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storing invoice jasa: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan internal saat menyimpan invoice jasa.'], 500);
        }
    }

    public function exportPO(Request $request)
    {
        $bulan = $request->query('bulan', date('n'));
        $tahun = $request->query('tahun', date('Y'));
        $jenis = $request->query('jenis');

        $namaBulan = $bulan == '0' ? 'SemuaBulan' : Carbon::create()->month($bulan)->format('F');
        $filename = "Laporan_PO_NonKertas_{$namaBulan}_{$tahun}.xlsx";
        return Excel::download(new PoKertasExport($bulan, $tahun, $jenis), $filename);
    }

    public function exportExcel(Request $request)
    {
        $user = session('user_data');
        if (!$user || $user['type'] != 11) {
            return abort(403);
        }

        $latestSO = DB::table('stok_opname')->orderBy('tanggal', 'desc')->first();
        if (!$latestSO) {
            return back()->with('error', 'Data Stock Opname tidak ditemukan.');
        }

        $tglMulai = $latestSO->tanggal;
        $tglAkhir = $request->query('tanggal_akhir') ?? date('Y-m-d');

        return Excel::download(
            new LaporanOpnameExport($tglMulai, $tglAkhir),
            "Laporan-Pemakaian-Sampai-{$tglAkhir}.xlsx"
        );
    }

    public function exportLaporan(Request $request)
    {
        $user = session('user_data');
        if (!$user || $user['type'] != 11) {
            return abort(403);
        }

        $tglAwal = $request->query('tgl_awal');
        $tglAkhir = $request->query('tgl_akhir');

        if (!$tglAwal || !$tglAkhir) {
            return back()->with('error', 'Periode tanggal tidak valid.');
        }

        return Excel::download(
            new ExportLaporan($tglAwal, $tglAkhir),
            "Laporan-Stok-Barang-{$tglAwal}-sd-{$tglAkhir}.xlsx"
        );
    }
}
