<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\POModel; // Menggunakan Model Anda
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Diperlukan untuk beberapa query
use Carbon\Carbon;

class InvPoController extends Controller
{
    /**
     * Menyediakan data untuk server-side DataTables.
     * Endpoint: GET /api/purchase-orders
     */
    public function index(Request $request)
    {
        $request->validate(['jenis' => 'required|in:0,1,2']);
        $draw = $request->input('draw');
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $searchValue = $request->input('search.value');
        $jenis = $request->input('jenis');
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        $query = POModel::query()->where('inv_po.jenis', $jenis);
        if ($bulan && $bulan != '0') {
            $query->whereMonth('inv_po.tanggal', '=', $bulan);
        }
        if ($tahun) {
            $query->whereYear('inv_po.tanggal', '=', $tahun);
        }

        $recordsTotal = $query->count();

        if (!empty($searchValue)) {
            $query->join('suppliers', 'inv_po.id_suplier', '=', 'suppliers.id')
                ->where(function ($q) use ($searchValue) {
                    $q->where('inv_po.no_po', 'like', "%{$searchValue}%")
                        ->orWhere('inv_po.no_order', 'like', "%{$searchValue}%")
                        ->orWhere('suppliers.nama', 'like', "%{$searchValue}%");
                });
        }

        $recordsFiltered = $query->count();

        $dataQuery = $query->offset($start)
            ->limit($length)
            ->orderBy('inv_po.tanggal', 'desc')
            ->orderBy('inv_po.id', 'desc');

        if (empty($searchValue)) {
            $dataQuery->join('suppliers', 'inv_po.id_suplier', '=', 'suppliers.id');
        }

        // Select kolom yang dibutuhkan
        $data = $dataQuery->select(
            'inv_po.no_po',
            'inv_po.no_order',
            'inv_po.tanggal',
            'suppliers.nama as nama_supplier',
            'inv_po.GrandTotalPembelian',
            'inv_po.status',
            'inv_po.kunci'
        )->get();

        // 7. Kembalikan response
        return response()->json([
            'draw'            => intval($draw),
            'recordsTotal'    => intval($recordsTotal),
            'recordsFiltered' => intval($recordsFiltered),
            'data'            => $data
        ]);
    }
    public function toggleLockStatus(Request $request)
    {
        // Validasi input, pastikan nomorpo dikirim
        $request->validate([
            'nomorpo' => 'required|string|exists:inv_po,no_po'
        ]);

        $nomorpo = $request->input('nomorpo');

        // Gunakan POModel Anda untuk mencari data di database inventory
        $po = POModel::find($nomorpo);

        // Logika toggle, sama persis seperti contoh Anda
        if ($po) {
            // Jika kunci adalah 1, ubah jadi 0. Jika 0, ubah jadi 1.
            $po->kunci = $po->kunci == 1 ? 0 : 1;
            $po->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Status kunci berhasil diperbarui.',
                'kunci_baru' => $po->kunci // Kirim status kunci yang baru
            ]);
        }

        // Ini tidak akan terjadi jika validasi 'exists' di atas bekerja,
        // tapi ini adalah pengaman tambahan.
        return response()->json([
            'status' => 'error',
            'message' => 'PO tidak ditemukan.'
        ], 404);
    }
    public function show($nomorpo)
    {
        // 'with()' akan otomatis memanggil fungsi relasi 'supplier' dan 'details'
        // yang baru saja kita buat di POModel.
        $po = POModel::with(['supplier', 'details.bahan'])->findOrFail($nomorpo);

        return response()->json($po);
    }
    public function monthlyPurchases(Request $request)
    {
        // 1. Validasi input
        $validated = $request->validate([
            'tahun' => 'required|integer|digits:4',
        ]);

        $tahunTerpilih = $validated['tahun'];
        $tahunPembanding = $tahunTerpilih - 1;

        // 2. Query untuk mengambil dan menghitung total pembelian per bulan untuk DUA TAHUN
        $data = DB::table('admin_lpb as lpb')
            ->join('admin_lpb_detail as lpb_detail', 'lpb.id_lpb', '=', 'lpb_detail.id_lpb')
            ->join('inv_po as po', 'lpb.no_po', '=', 'po.no_po')
            ->join('inv_podetail as po_detail', function ($join) {
                $join->on('po.no_po', '=', 'po_detail.no_po')
                    ->on('lpb_detail.id_bahan', '=', 'po_detail.id_bahan');
            })
            ->select(
                DB::raw('MONTH(lpb.tanggal) as bulan'),
                // Kalkulasi untuk tahun yang dipilih
                DB::raw('SUM(CASE WHEN YEAR(lpb.tanggal) = ? THEN (lpb_detail.jumlah_barang_diterima * po_detail.harga) ELSE 0 END) as total_tahun_terpilih'),
                // Kalkulasi untuk tahun pembanding
                DB::raw('SUM(CASE WHEN YEAR(lpb.tanggal) = ? THEN (lpb_detail.jumlah_barang_diterima * po_detail.harga) ELSE 0 END) as total_tahun_pembanding')
            )
            ->where('lpb.flag', 0)
            // Filter untuk kedua tahun
            ->whereIn(DB::raw('YEAR(lpb.tanggal)'), [$tahunTerpilih, $tahunPembanding])
            ->groupBy(DB::raw('MONTH(lpb.tanggal)'))
            // Binding parameter ke SELECT clause
            ->addBinding([$tahunTerpilih, $tahunPembanding], 'select')
            ->get()
            ->keyBy('bulan');

        // 3. Siapkan array 12 bulan
        $reportData = [];
        for ($i = 1; $i <= 12; $i++) {
            $reportData[] = [
                'bulan' => $i,
                'nama_bulan' => Carbon::create()->month($i)->translatedFormat('F'),
                'total_tahun_terpilih' => 0,
                'total_tahun_pembanding' => 0,
            ];
        }

        // 4. Isi data dari hasil query ke dalam array 12 bulan
        foreach ($reportData as &$monthData) {
            $bulan = $monthData['bulan'];
            if (isset($data[$bulan])) {
                $monthData['total_tahun_terpilih'] = (float) $data[$bulan]->total_tahun_terpilih;
                $monthData['total_tahun_pembanding'] = (float) $data[$bulan]->total_tahun_pembanding;
            }
        }

        // 5. Kembalikan sebagai respons JSON
        return response()->json([
            'tahun_terpilih' => $tahunTerpilih,
            'tahun_pembanding' => $tahunPembanding,
            'data' => $reportData
        ]);
    }
}
