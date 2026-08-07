<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\KategoriBahan;
use App\Models\Pengambilan;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\NpkExport;
use Maatwebsite\Excel\Facades\Excel;

class Pengambilancontroller extends Controller
{
    public function home(Request $request, $jenis)
    {
        $data = [
            'title' => $jenis == '0' ? 'Pengambilan Barang Penunjang (PO) & Barang Penolong (PP)' : ($jenis == '3' ? 'Pengambilan Barang Non PO/PP' : 'Pengambilan Tinta'),
        ];
        $user = session('user_data');

        $gudang = DB::table('admin_namagudang')->select('id', 'nama')->get();

        return view('pengambilan.index', compact('jenis', 'user', 'data', 'gudang'));
    }

    public function getbarang(Request $request)
    {
        $kategorix = $request->input('kategori');
        $barang = Bahan::from('bahan as b')
            ->leftJoin('admin_namagudang as g', 'b.gudang', '=', 'g.id')
            ->orderBy('b.nama', 'asc');

        if ($kategorix == '0') {
            $barang->where(function ($query) {
                $query->where('b.jenis', 0)->where('b.kategori', '!=', 1)
                    ->orWhere('b.jenis', 1);
            });
        } elseif ($kategorix == '3') {
            $barang->where('b.jenis', 2);
        } else {
            $barang->where('b.jenis', 0)->where('b.kategori', 1);
        }

        $result = $barang->get([
            'b.id',
            'b.nama',
            'b.satuan',
            'b.satuan_kecil',
            'b.berat_kecil',
            'b.gudang',
            'g.nama as nama_gudang_asal',
            'b.stok_onhand'
        ]);

        return response()->json($result);
    }

    public function adddata(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'tanggal' => 'required|date',
                'keterangan' => 'required|string|max:60',
                'barang' => 'required|string|max:12',
                'operator' => 'required|string',
                'jumlah' => 'required|numeric',
                'id_gudang_asal' => 'required|numeric',
                'id_gudang_tujuan' => 'required|numeric',
            ]);

            $kodex = '';

            if ($request->input('nomor')) {
                $kodex = $request->input('nomor');

                Pengambilan::where('kode', $kodex)->update([
                    'tanggal' => $validatedData['tanggal'],
                    'keterangan' => $validatedData['keterangan'],
                    'operator' => $validatedData['operator'],
                ]);

                $newDetail = new Pengambilan();
                $newDetail->kode = $kodex;
                $newDetail->tanggal = $validatedData['tanggal'];
                $newDetail->keterangan = $validatedData['keterangan'];
                $newDetail->id_barang = $validatedData['barang'];
                $newDetail->id_gudang_asal = $validatedData['id_gudang_asal'];
                $newDetail->id_gudang_tujuan = $validatedData['id_gudang_tujuan'];
                $newDetail->operator = $validatedData['operator'];
                $newDetail->jumlah = $validatedData['jumlah'];
                $newDetail->save();
            } else {
                $basePrefix = match ($request->input('flag')) {
                    '2' => 'NPBT',
                    '0' => 'NPBPO',
                    '1' => 'NPBPP',
                    '3' => 'NPBMO',
                    default => 'NPB',
                };

                $dateParam = Carbon::parse($validatedData['tanggal'])->format('ymd');
                $searchPrefix = $basePrefix . $dateParam;

                $lastKode = Pengambilan::where('kode', 'like', $searchPrefix . '%')
                    ->orderBy('kode', 'desc')
                    ->first();

                $lastNumber = $lastKode ? intval(substr($lastKode->kode, -3)) : 0;
                $increment = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

                $kodex = $searchPrefix . $increment;

                $inputx = new Pengambilan();
                $inputx->kode = $kodex;
                $inputx->tanggal = $validatedData['tanggal'];
                $inputx->keterangan = $validatedData['keterangan'];
                $inputx->id_barang = $validatedData['barang'];
                $inputx->id_gudang_asal = $validatedData['id_gudang_asal'];
                $inputx->id_gudang_tujuan = $validatedData['id_gudang_tujuan'];
                $inputx->operator = $validatedData['operator'];
                $inputx->jumlah = $validatedData['jumlah'];
                $inputx->save();
            }

            return response()->json([
                'status' => 'ok',
                'message' => 'Data berhasil disimpan!',
                'kodenpkplanning' => $kodex,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function listdata(Request $request)
    {
        $flagx = $request->input('flag');
        $kodex = $request->input('kode');
        $jenisx = $request->input('jenis');
        $periodex = $request->input('periode');

        if ($kodex || $kodex == '0') {
            $query = DB::table('npk_planning as a')
                ->join('bahan as b', 'a.id_barang', '=', 'b.id')
                ->select('a.id', 'b.nama', 'a.jumlah', 'b.satuan')
                ->where('a.kode', '=', $kodex)
                ->orderBy('a.id', 'asc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '<a href="#" class="btn btn-danger btn-sm hapusdetail" data-id="' . $row->id . '">
                            <i class="fas fa-trash"></i>
                        </a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $query = DB::table('npk_planning as a')
            ->join('bahan as b', 'a.id_barang', '=', 'b.id')
            ->select(
                'a.id',
                'a.kode',
                'a.tanggal',
                'a.keterangan',
                'a.id_barang',
                'a.id_user',
                'b.nama',
                'b.satuan',
                'a.jumlah',
                'a.operator',
                'a.id_gudang_tujuan',
                'a.jumlah_terkirim',
                'a.tgl_terkirim'
            );

        if ($jenisx == '2') {
            $query->where('a.kode', 'LIKE', 'NPBT%');
        } elseif ($jenisx == '3') {
            $query->where('a.kode', 'LIKE', 'NPBMO%');
        } else {
            $query->where(function ($q) {
                $q->where('a.kode', 'LIKE', 'NPBPO%')
                    ->orWhere('a.kode', 'LIKE', 'NPBBT%');
            });
        }

        if ($flagx == '1') {
            $query->where('a.close', '=', 0);
        } elseif ($flagx == '2') {
            $query->where('a.jumlah_terkirim', '!=', 0);
            if ($periodex) {
                $query->where('a.tanggal', 'LIKE', $periodex . '%');
            }
        }
        $query->orderBy('a.kode', 'desc');
        return DataTables::of($query)
            ->addColumn('action', function ($row) use ($flagx) {
                $btn = '';
                if ($flagx == '1') {
                    $btn = '<a href="#" class="btn btn-success btn-sm editnpk" 
                            data-kode="' . $row->kode . '" 
                            data-tanggal="' . $row->tanggal . '" 
                            data-keterangan="' . $row->keterangan . '" 
                            data-gudang_tujuan="' . $row->id_gudang_tujuan . '" 
                            data-operator="' . $row->operator . '">
                            <i class="far fa-edit"></i>
                        </a>
                        <a href="#" class="btn bg-yellow btn-sm proses" 
                            data-kode="' . $row->kode . '" 
                            data-tanggal="' . $row->tanggal . '">
                            <i class="fas fa-sync"></i> Proses
                        </a>';
                }
                return $btn;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function destroy(Request $request, $idx)
    {
        DB::beginTransaction();
        try {
            $kodeid = $idx;
            $npkdetail = Pengambilan::find($kodeid);
            if ($npkdetail) {
                $npkdetail->delete();
                DB::commit();
                return response()->json([
                    'succes' => 'ok',
                    'pesan' => 'Detail berhasil dihapus',
                ], 200);
            } else {
                return response()->json([
                    'succes' => 'error',
                    'pesan' => 'Detail NPK Planning tidak ditemukan',
                ], 404);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['succes' => 'error', 'pesan' => $e->getMessage()]);
        }
    }

    public function updateTanggalKirim(Request $request)
    {
        $request->validate([
            'kode' => 'required|string',
            'tanggal_kirim' => 'required|date',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $today = now()->format('ymd');
                $prefix = 'BOK_' . $today;

                $lastEntry = DB::table('bahan_produksi')
                    ->where('kode', 'like', $prefix . '%')
                    ->orderBy('kode', 'desc')
                    ->lockForUpdate()
                    ->first();

                $sequence = 1;
                if ($lastEntry) {
                    $lastSequence = (int) substr($lastEntry->kode, -3);
                    $sequence = $lastSequence + 1;
                }
                $generatedBokCode = $prefix . str_pad($sequence, 3, '0', STR_PAD_LEFT);

                $items = DB::table('npk_planning as npk')
                    ->join('bahan as b', 'npk.id_barang', '=', 'b.id')
                    ->where('npk.kode', $request->kode)
                    ->where('npk.close', 0)
                    ->select('npk.*', 'b.kategori', 'b.satuan')
                    ->get();

                if ($items->isEmpty()) {
                    return response()->json(['success' => false, 'message' => 'Data tidak ditemukan atau sudah ditutup.']);
                }

                $dataToInsert = $items->reject(function ($item) {
                    return $item->id_gudang_asal == $item->id_gudang_tujuan;
                })->map(function ($item) use ($generatedBokCode) {
                    return [
                        'nama' => $item->id_barang,
                        'kategori' => $item->kategori,
                        'id_gudang' => $item->id_gudang_tujuan,
                        'satuan' => $item->satuan,
                        'jumlah' => $item->jumlah,
                        'dipakai' => 0,
                        'untuk_npk' => json_encode([$item->kode]),
                        'kode' => $generatedBokCode,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray();

                if (!empty($dataToInsert)) {
                    DB::table('bahan_produksi')->insert($dataToInsert);
                }

                $updatedRows = DB::table('npk_planning')
                    ->where('kode', $request->kode)
                    ->where('close', 0)
                    ->update([
                        'tgl_terkirim' => $request->tanggal_kirim,
                        'jumlah_terkirim' => DB::raw('`jumlah`'),
                        'close' => 1,
                        'updated_at' => now(),
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => $updatedRows . ' item berhasil ditutup. Kode produksi: ' . $generatedBokCode
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function exportNpk(Request $request)
    {
        $request->validate([
            'periode' => 'required|date_format:Y-m',
            'flag' => 'required|in:1,2',
            'jenis' => 'required',
        ]);

        $periode = $request->query('periode');
        $flag = $request->query('flag');
        $jenis = $request->query('jenis');

        $fileName = 'Laporan_Pengeluaran_' . $periode . '.xlsx';
        return Excel::download(new NpkExport($periode, $flag, $jenis), $fileName);
    }

    public function apiNextPackingNumber(Request $request)
    {
        $tanggalInput = $request->query('tanggal', now()->toDateString());
        $tgl = \Carbon\Carbon::parse($tanggalInput);

        $flag = $request->query('flag', 'PO');
        $basePrefix = 'NPB' . $flag;
        $dateParam = $tgl->format('ymd');
        $searchPrefix = $basePrefix . $dateParam;

        $lastKode = DB::table('npk_planning')
            ->where('kode', 'like', $searchPrefix . '%')
            ->orderBy('kode', 'desc')
            ->value('kode');

        if ($lastKode) {
            $lastNumber = (int) substr($lastKode, strlen($searchPrefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $nomorUrut = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        $kodeBaru = $searchPrefix . $nomorUrut;

        return response()->json([
            'status' => 'ok',
            'kode' => $kodeBaru,
            'info' => [
                'tanggal_digunakan' => $tanggalInput,
                'prefix' => $searchPrefix
            ]
        ]);
    }

    public function apiStorePlanningPacking(Request $request)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'keterangan' => 'required|string|max:60',
                'barang' => 'required|integer',
                'operator' => 'required|string',
                'jumlah' => 'required|numeric',
                'nomor' => 'nullable|string',
                'kode_datapesanan' => 'required|string',
            ]);

            $kode = $validated['nomor'] ?? null;

            if ($kode) {
                $header = Pengambilan::where('kode', $kode)->first();

                if (!$header) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Nomor NPB tidak ditemukan: ' . $kode,
                    ], 404);
                }

                $header->update([
                    'tanggal' => $validated['tanggal'],
                    'keterangan' => $validated['keterangan'],
                    'operator' => $validated['operator'],
                ]);
            } else {
                $flag = 'PO';
                $basePrefix = 'NPB' . $flag;
                $dateParam = date('ymd', strtotime($validated['tanggal']));
                $searchPrefix = $basePrefix . $dateParam;
                $lastRecord = Pengambilan::where('kode', 'like', $searchPrefix . '%')
                    ->orderBy('kode', 'desc')
                    ->first();

                if ($lastRecord) {
                    $lastNumber = intval(substr($lastRecord->kode, strlen($searchPrefix)));
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 1;
                }

                $nomorUrut = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
                $kode = $searchPrefix . $nomorUrut;
            }

            $detail = new Pengambilan();
            $detail->kode = $kode;
            $detail->tanggal = $validated['tanggal'];
            $detail->keterangan = $validated['keterangan'];
            $detail->id_barang = $validated['barang'];
            $detail->operator = $validated['operator'];
            $detail->jumlah = $validated['jumlah'];
            $detail->kode_datapesanan = $validated['kode_datapesanan'];
            $detail->save();

            return response()->json([
                'status' => 'ok',
                'message' => 'Planning Packing berhasil disimpan.',
                'kode' => $kode,
                'detail_id' => $detail->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function apiDeletePlanningPackingDetail($id)
    {
        $row = Pengambilan::find($id);

        if (!$row) {
            return response()->json([
                'status' => 'error',
                'message' => 'Detail planning packing tidak ditemukan.',
            ], 404);
        }

        if (!empty($row->jumlah_terkirim) && $row->jumlah_terkirim > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sudah ada jumlah_terkirim, detail tidak boleh dihapus.',
            ], 422);
        }

        $row->delete();

        return response()->json([
            'status' => 'ok',
            'message' => 'Detail planning packing berhasil dihapus.',
        ]);
    }

    public function apiGetPlanningPackingByDp($kodeDatapesanan)
    {
        $rows = DB::table('npk_planning as p')
            ->join('bahan as b', 'p.id_barang', '=', 'b.id')
            ->where('p.kode_datapesanan', $kodeDatapesanan)
            ->where('p.kode', 'like', 'NPBPO%')
            ->orderBy('p.id')
            ->select('p.id', 'p.kode', 'p.tanggal', 'p.keterangan', 'p.jumlah', 'b.nama as nama_barang', 'b.satuan')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'status' => 'ok',
                'kode' => null,
                'tanggal' => null,
                'keterangan' => null,
                'details' => [],
            ]);
        }

        $first = $rows->first();

        return response()->json([
            'status' => 'ok',
            'kode' => $first->kode,
            'tanggal' => $first->tanggal,
            'keterangan' => $first->keterangan,
            'details' => $rows
                ->map(function ($row) {
                    return [
                        'id' => $row->id,
                        'kode' => $row->kode,
                        'nama_barang' => $row->nama_barang,
                        'jumlah' => (float) $row->jumlah,
                        'satuan' => $row->satuan,
                    ];
                })
                ->all(),
        ]);
    }
}
