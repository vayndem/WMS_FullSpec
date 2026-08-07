<?php

namespace App\Http\Controllers;

use App\Models\BahanProduksi;
use App\Models\BahanProduksiDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Support\Facades\Http;
use Exception;

class BahanProduksiController extends Controller
{
    public function index(Request $request): View
    {
        $user = session('user_data');
        if (!$user) {
            abort(403, 'Sesi berakhir, silakan login kembali.');
        }

        $query = BahanProduksi::with(['detailBahan', 'detailPemakaian']);

        if ($request->filled('bulan')) {
            $query->whereMonth('created_at', $request->bulan);
        }

        $tahun = $request->input('tahun', date('Y'));
        $query->whereYear('created_at', $tahun);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'LIKE', "%{$search}%")
                    ->orWhere('untuk_npk', 'LIKE', "%{$search}%")
                    ->orWhereHas('detailBahan', function ($qBahan) use ($search) {
                        $qBahan->where('nama', 'LIKE', "%{$search}%");
                    });
            });
        }

        $data = $query->orderBy('nama')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('nama');

        return view('bahan_produksi.update', compact('data', 'user'));
    }

    public function index2(Request $request): View
    {
        $user = session('user_data');
        if (!$user) {
            abort(403, 'Sesi berakhir, silakan login kembali.');
        }

        $categories = DB::table('kategori_bahan')->orderBy('katnama', 'asc')->get();
        $gudangs = DB::table('admin_namagudang')->orderBy('id', 'asc')->get();
        $kategoriFilter = $request->input('kategori', optional($categories->first())->katid);
        $gudangFilter = $request->input('gudang', optional($gudangs->first())->id);

        $query = BahanProduksi::join('bahan as b', 'bahan_produksi.nama', '=', 'b.id')
            ->join('kategori_bahan as kb', 'b.kategori', '=', 'kb.katid')
            ->select(
                'bahan_produksi.nama',
                'b.nama as nama_barang',
                'b.satuan',
                'kb.katnama',
                'kb.katid',
                DB::raw('SUM(bahan_produksi.jumlah) as total_masuk'),
                DB::raw('SUM(bahan_produksi.dipakai) as total_terpakai')
            );

        if (!empty($gudangFilter)) {
            $query->where('bahan_produksi.id_gudang', $gudangFilter);
        }

        if (!empty($kategoriFilter)) {
            $query->where('kb.katid', $kategoriFilter);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('b.nama', 'LIKE', "%{$search}%");
        }

        $idGudangFilter = $gudangFilter;

        $summary = $query->groupBy('bahan_produksi.nama', 'b.nama', 'b.satuan', 'kb.katnama', 'kb.katid')
            ->get()
            ->map(function ($item) use ($idGudangFilter) {
                $item->sisa_stok = max($item->total_masuk - $item->total_terpakai, 0);

                $sumberQuery = DB::table('bahan_produksi')->where('nama', $item->nama);
                if (!empty($idGudangFilter)) {
                    $sumberQuery->where('id_gudang', $idGudangFilter);
                }
                $item->sumber_npk = $sumberQuery->select('kode', 'untuk_npk', 'jumlah', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($row) {
                        $decoded = json_decode($row->untuk_npk, true);
                        $row->npk_string = is_array($decoded) ? implode(', ', $decoded) : $row->untuk_npk;
                        return $row;
                    });

                $riwayatQuery = DB::table('bahan_produksi_detail as bpd')
                    ->join('bahan_produksi as bp', 'bpd.id_produksi', '=', 'bp.id')
                    ->where('bp.nama', $item->nama);
                if (!empty($idGudangFilter)) {
                    $riwayatQuery->where('bp.id_gudang', $idGudangFilter);
                }
                $item->riwayat_detail = $riwayatQuery->select('bpd.data_pesanan', 'bpd.dipakai', 'bpd.created_at', 'bpd.keterangan')
                    ->orderBy('bpd.created_at', 'desc')
                    ->get();

                return $item;
            })
            ->sortByDesc('sisa_stok')
            ->values();

        return view('bahan_produksi.dashboard', compact('summary', 'categories', 'gudangs', 'user'));
    }

    public function indexGudang(Request $request, $id_gudang): View
    {
        $user = session('user_data');
        if (!$user) {
            abort(403, 'Sesi berakhir, silakan login kembali.');
        }

        $gudangAktif = DB::table('admin_namagudang')->where('id', $id_gudang)->first();
        if (!$gudangAktif) {
            abort(404, 'Gudang tidak ditemukan.');
        }

        $query = BahanProduksi::with(['detailBahan', 'detailPemakaian'])
            ->where('id_gudang', $id_gudang);

        if ($request->filled('bulan')) {
            $query->whereMonth('created_at', $request->bulan);
        }

        $tahun = $request->input('tahun', date('Y'));
        $query->whereYear('created_at', $tahun);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'LIKE', "%{$search}%")
                    ->orWhere('untuk_npk', 'LIKE', "%{$search}%")
                    ->orWhereHas('detailBahan', function ($qBahan) use ($search) {
                        $qBahan->where('nama', 'LIKE', "%{$search}%");
                    });
            });
        }

        $data = $query->orderBy('nama')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('nama');

        return view('bahan_produksi.update', compact('data', 'user', 'gudangAktif'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id'        => 'required|integer|unique:bahan_produksi,id',
            'kategori'  => 'nullable|integer',
            'nama'      => 'required|string',
            'untuk_npk' => 'nullable|string',
            'jumlah'    => 'nullable|numeric',
            'dipakai'   => 'nullable|numeric',
            'satuan'    => 'nullable|string'
        ]);

        if (!empty($validated['untuk_npk'])) {
            $validated['untuk_npk'] = array_map('trim', explode(',', $validated['untuk_npk']));
        }

        BahanProduksi::create($validated);

        return redirect()->route('bahan_produksi.index')->with('success', 'Data berhasil ditambahkan');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $barang = BahanProduksi::with('detailBahan')->findOrFail($id);

        if ($request->has('dipakai_kecil')) {
            $faktorKonversi = floatval($barang->detailBahan->berat_kecil ?? 1);
            $satuanKecil = $barang->detailBahan->satuan_kecil ?? $barang->satuan;

            $jumlahKecil = $barang->jumlah * $faktorKonversi;
            $dipakaiAwalKecil = $barang->dipakai * $faktorKonversi;
            $maxTambahanKecil = max($jumlahKecil - $dipakaiAwalKecil, 0);

            $request->validate([
                'dipakai_kecil' => "required|numeric|min:0|max:{$maxTambahanKecil}"
            ], [
                'dipakai_kecil.max' => "Jumlah dipakai tidak boleh melebihi sisa rencana ({$maxTambahanKecil} {$satuanKecil})"
            ]);

            $inputBesar = $faktorKonversi > 0 ? (floatval($request->dipakai_kecil) / $faktorKonversi) : floatval($request->dipakai_kecil);
            $totalDipakaiBesar = $barang->dipakai + $inputBesar;

            $barang->update(['dipakai' => $totalDipakaiBesar]);

            BahanProduksiDetail::create([
                'id_produksi'  => $barang->id,
                'dipakai'      => $inputBesar,
                'data_pesanan' => $request->input('data_pesanan', null),
                'keterangan'   => $request->input('keterangan', 'Update pemakaian via sistem')
            ]);

            $msg = "Data pemakaian berhasil diupdate.";
        } elseif ($request->has('untuk_npk')) {
            $request->validate([
                'untuk_npk' => 'required|string'
            ]);

            $arrayData = array_map('trim', explode(',', $request->untuk_npk));
            $barang->update(['untuk_npk' => $arrayData]);

            $msg = "Deskripsi berhasil diperbarui.";
        }

        return redirect()->route('bahan_produksi.index')->with('success', $msg ?? 'Data berhasil diperbarui');
    }

    public function destroy($id): RedirectResponse
    {
        $barang = BahanProduksi::findOrFail($id);
        BahanProduksiDetail::where('id_produksi', $barang->id)->delete();
        $barang->delete();

        return redirect()->route('bahan_produksi.index')->with('success', 'Data berhasil dihapus');
    }

    public function generate($id)
    {
        $item = BahanProduksi::with('detailBahan')->findOrFail($id);

        return view('bahan_produksi.cetak', compact('item'));
    }

    public function verifyScan(Request $request): JsonResponse
    {
        $request->validate([
            'scanned_data' => 'required|string'
        ]);

        try {
            $decoded = json_decode($request->scanned_data, true);

            if (!$decoded || !isset($decoded['system']) || !isset($decoded['target_id']) || !isset($decoded['batch_key'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format atau struktur data QR Code tidak valid.'
                ], 422);
            }

            if ($decoded['system'] !== 'INVENTORY') {
                return response()->json([
                    'success' => false,
                    'message' => 'Format QR Code tidak dikenali oleh sistem ini.'
                ], 422);
            }

            $item = BahanProduksi::with('detailBahan')
                ->where('id', $decoded['target_id'])
                ->where('kode', $decoded['batch_key'])
                ->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data barang tidak ditemukan atau kode batch tidak sesuai.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'item_id' => $item->id,
                'nama'    => $item->detailBahan->nama ?? 'Bahan',
                'message' => 'Barang ditemukan.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca data QR: ' . $e->getMessage()
            ], 500);
        }
    }

    public function create()
    {
        return abort(404);
    }

    public function edit($id)
    {
        return abort(404);
    }

    public function show($id)
    {
        return abort(404);
    }

    public function getdatapesanan(Request $request)
    {
        $apiUrlBase = env('INVENTORY_API_URL');
        $apiToken = env('INVENTORY_API_TOKEN');

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $apiToken,
                'Accept'    => 'application/json',
            ])->get($apiUrlBase . '/get-data');

            if ($response->failed()) {
                throw new Exception("Gagal terhubung ke Sistem A");
            }

            $hasil = $response->json();
            $dataPesanan = $hasil['data'] ?? [];

            $dataTerbaru = array_reverse($dataPesanan);

            return response()->json([
                'success' => true,
                'data' => $dataTerbaru
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function returnBahan(Request $request): JsonResponse
    {
        $request->validate([
            'id_detail'  => 'required|integer|exists:bahan_produksi_detail,id',
            'jumlah'     => 'required|numeric|min:0.01',
            'keterangan' => 'nullable|string'
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $detailLama = BahanProduksiDetail::findOrFail($request->id_detail);
                $barang = BahanProduksi::with('detailBahan')->findOrFail($detailLama->id_produksi);

                $faktorKonversi = floatval($barang->detailBahan->berat_kecil ?? 1);
                $jumlahInputBesar = $faktorKonversi > 0 ? (floatval($request->jumlah) / $faktorKonversi) : floatval($request->jumlah);

                if ($jumlahInputBesar > $detailLama->dipakai) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Jumlah koreksi melebihi kuantitas pemakaian asli.'
                    ], 422);
                }

                $nilaiMinus = -1 * abs($jumlahInputBesar);

                $barang->update([
                    'dipakai' => max($barang->dipakai - $jumlahInputBesar, 0)
                ]);

                BahanProduksiDetail::create([
                    'id_produksi'  => $barang->id,
                    'dipakai'      => $nilaiMinus,
                    'data_pesanan' => $detailLama->data_pesanan,
                    'keterangan'   => $request->input('keterangan') ?: 'Koreksi pengembalian bahan otomatis'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Koreksi pengembalian bahan sebesar ' . $request->jumlah . ' berhasil diproses.'
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function kembaliBahan(Request $request): JsonResponse
    {
        $request->validate([
            'id_produksi' => 'required|integer|exists:bahan_produksi,id',
            'jumlah'      => 'required|numeric|min:0.01',
            'keterangan'  => 'nullable|string'
        ]);

        $maxAttempts = 5;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                $attempt++;
                return DB::transaction(function () use ($request) {
                    $barang = DB::table('bahan_produksi')
                        ->where('id', $request->id_produksi)
                        ->lockForUpdate()
                        ->first();

                    if (!$barang) {
                        return response()->json(['success' => false, 'message' => 'Data produksi tidak ditemukan.'], 404);
                    }

                    $detailBahan = DB::table('bahan')->where('id', $barang->nama)->first();
                    if (!$detailBahan) {
                        return response()->json(['success' => false, 'message' => 'Master bahan tidak ditemukan.'], 404);
                    }

                    $faktorKonversi = floatval($detailBahan->berat_kecil ?? 1);
                    $jumlahInputBesar = $faktorKonversi > 0 ? (floatval($request->jumlah) / $faktorKonversi) : floatval($request->jumlah);

                    $sisaRencanaProduksi = max($barang->jumlah - $barang->dipakai, 0);

                    if ($jumlahInputBesar > $sisaRencanaProduksi) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Jumlah pengembalian melebihi sisa bahan di produksi (' . ($sisaRencanaProduksi * $faktorKonversi) . ').'
                        ], 422);
                    }

                    DB::table('bahan_produksi')
                        ->where('id', $request->id_produksi)
                        ->update([
                            'jumlah' => max($barang->jumlah - $jumlahInputBesar, 0),
                            'updated_at' => now()
                        ]);

                    DB::table('bahan')
                        ->where('id', $barang->nama)
                        ->increment('stok_onhand', $jumlahInputBesar);

                    $tahun = date('Y');
                    $prefix = 'KRK' . $tahun;

                    $lastLpb = DB::table('admin_lpb')
                        ->where('id_lpb', 'like', $prefix . '%')
                        ->orderBy('id_lpb', 'desc')
                        ->lockForUpdate()
                        ->first();

                    $sequence = 1;
                    if ($lastLpb) {
                        $lastSequence = (int) substr($lastLpb->id_lpb, -3);
                        $sequence = $lastSequence + 1;
                    }
                    $generatedLpbCode = $prefix . str_pad($sequence, 3, '0', STR_PAD_LEFT);

                    $hargaTerakhir = DB::table('admin_lpb_detail')
                        ->where('id_bahan', $barang->nama)
                        ->orderBy('id', 'desc')
                        ->value('harga') ?? 0;

                    DB::table('admin_lpb')->insert([
                        'id_lpb'     => $generatedLpbCode,
                        'tanggal'    => now()->format('Y-m-d'),
                        'no_po'      => '-',
                        'no_sj'      => $request->keterangan ?? '-',
                        'id_user'    => auth()->id() ?? 38,
                        'flag'       => 0,
                        'status'     => 0,
                        'jenis_lpb'  => 99,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('admin_lpb_detail')->insert([
                        'id_lpb'                 => $generatedLpbCode,
                        'id_bahan'               => $barang->nama,
                        'id_kategori'            => $barang->kategori,
                        'jumlah_barang_diterima' => $jumlahInputBesar,
                        'lot_number'             => '-',
                        'harga'                  => $hargaTerakhir,
                        'jumlah_dipakai'         => 0,
                        'flag_dipakai'           => 1,
                        'created_at'             => now(),
                        'updated_at'             => now(),
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Bahan berhasil dikembalikan ke gudang dengan nomor dokumen: ' . $generatedLpbCode
                    ]);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() == '23000' && $attempt < $maxAttempts) {
                    usleep(100000);
                    continue;
                }
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan database: ' . $e->getMessage()
                ], 500);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan internal: ' . $e->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal membuat nomor dokumen unik setelah beberapa kali percobaan. Silakan coba lagi.'
        ], 500);
    }
}
