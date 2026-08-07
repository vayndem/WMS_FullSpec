<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\Permintaan;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Exports\PermintaanExport;
use Maatwebsite\Excel\Facades\Excel;

class PermintaanController extends Controller
{
    public function index() {}
    public function home(Request $request, $jenis)
    {
        if ($request->ajax()) {
            // Ambil parameter status dari request
            $status = $request->status;

            // Mengambil data permintaan dengan filter jenis menggunakan join manual
            $permintaan = Permintaan::join('bahan', 'permintaan.id_bahan', '=', 'bahan.id')
                ->where('bahan.jenis', $jenis) // Memfilter berdasarkan jenis bahan
                ->when($status !== null && $status !== '', function ($query) use ($status) {
                    return $query->where('permintaan.finish', $status); // Filter status jika tidak kosong
                })
                ->orderBy('permintaan.id', 'desc') // Mengurutkan berdasarkan id secara descending
                ->select('permintaan.id', 'permintaan.id_bahan', 'permintaan.jumlah_order', 'permintaan.realisasi', 'permintaan.finish', 'permintaan.created_at', 'bahan.nama as bahan_nama', 'bahan.satuan as satuan'); // Menyertakan kolom bahan.nama

            return datatables()
                ->of($permintaan)
                ->addIndexColumn() // Menambahkan nomor urut otomatis
                ->addColumn('bahan', function ($row) {
                    return $row->bahan_nama ?? '-';
                })
                ->addColumn('status', function ($row) {
                    return $row->finish ? '<span class="badge badge-success">Selesai</span>' : '<span class="badge badge-warning">Proses</span>';
                })
                ->addColumn('aksi', function ($row) {
                    return '
                    <button id="editPermintaan_' .
                        $row->id .
                        '"
                        name="tombolEdit"
                        class="btn btn-sm btn-warning"
                        onclick="editPermintaan(' .
                        $row->id .
                        ', ' .
                        $row->jumlah_order .
                        ')">
                        Edit
                    </button>
                    <button id="deletePermintaan_' .
                        $row->id .
                        '"
                        name="tombolHapus"
                        class="btn btn-sm btn-danger"
                        onclick="deletePermintaan(' .
                        $row->id .
                        ')">
                        Hapus
                    </button>';
                })
                ->rawColumns(['status', 'aksi']) // Agar HTML di kolom status dan aksi tidak di-escape
                ->make(true);
        }

        // Jika bukan request AJAX, load halaman dengan jenis bahan
        $user = session('user_data');
        return view('permintaan.index', compact('jenis', 'user'));
    }
    public function exportExcel(Request $request, $jenis)
    {
        $status = $request->query('status');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (empty($startDate) || empty($endDate)) {
            return redirect()->back()->with('error', 'Tanggal awal dan akhir harus diisi untuk ekspor.');
        }

        $namaJenis = $jenis == 0 ? 'Penunjang' : 'Penolong';
        $fileName = 'Permintaan_' . $namaJenis . '_' . $startDate . '_sampai_' . $endDate . '.xlsx';
        return Excel::download(new PermintaanExport($jenis, $status, $startDate, $endDate), $fileName);
    }

    public function create(Request $request)
    {
        $jenis = $request->input('jenis');
        $data = [
            'title' => $jenis == '0' ? 'Bahan Penunjang' : 'Bahan Penolong',
        ];
        if ($request->ajax()) {
            $view = view('permintaan.caribahan', $data)->render();
            return response()->json(['data' => $view]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi data yang diterima
        $validatedData = $request->validate([
            'id_bahan' => 'required|integer',
            'jumlah_order' => 'required|numeric',
        ]);

        try {
            // Simpan data ke dalam tabel permintaan
            $permintaan = new Permintaan();
            $permintaan->id_bahan = $request->id_bahan;
            $permintaan->jumlah_order = $request->jumlah_order;
            $permintaan->realisasi = 0;
            $permintaan->finish = 0;
            $permintaan->save();

            Permintaan::whereColumn('realisasi', '>=', 'jumlah_order')->update(['finish' => 1]);

            // Jika berhasil disimpan, kirimkan respons JSON
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Permintaan berhasil disimpan.',
                    'data' => $permintaan, // Menyertakan data yang baru disimpan
                ],
                201,
            );
        } catch (\Exception $e) {
            // Jika terjadi kesalahan, kirimkan respons error
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($jenis)
    {
        $listx = Bahan::where('jenis', $jenis)->select('id', 'nama', 'keterangan_bahan', 'satuan')->orderBy('nama');

        return DataTables::of($listx)
            ->addIndexColumn()
            ->addColumn('action', function ($bahan) {
                $cetak = '<div style="text-align: center;"><button type="button" title="pilih bahan" name="pilihbahan" id="pilihbahan_' . $bahan->id . '" class="btn btn-info btn-sm ml-1" onclick="pilihbahan(\'' . $bahan->id . '\',\'' . $bahan->nama . '\')">Pilih</button></div>';
                return $cetak;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permintaan $permintaan)
    {
        // Return view if needed for form editing
        return response()->json(['message' => 'Use AJAX or API to edit']);
    }

    public function update(Request $request, $id)
    {
        // Validasi data
        $validatedData = $request->validate([
            'jumlah_order' => 'required|numeric',
        ]);

        // Cari permintaan berdasarkan ID
        $permintaan = Permintaan::find($id);

        // Jika permintaan tidak ditemukan, kembalikan response error
        if (!$permintaan) {
            return response()->json(['success' => false, 'message' => 'Permintaan tidak ditemukan.'], 404);
        }

        // Update data permintaan dengan data yang divalidasi
        $permintaan->update([
            'jumlah_order' => $validatedData['jumlah_order'],
        ]);
        Permintaan::whereColumn('realisasi', '>=', 'jumlah_order')->update(['finish' => 1]);
        // Kembalikan response sukses
        return response()->json(['success' => true, 'message' => 'Data permintaan berhasil diperbarui.']);
    }

    public function destroy(Permintaan $permintaan)
    {
        try {
            // Menghapus data permintaan
            $permintaan->delete();
            Permintaan::whereColumn('realisasi', '>=', 'jumlah_order')->update(['finish' => 1]);
            // Mengembalikan respon sukses jika data berhasil dihapus
            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus.']);
        } catch (\Exception $e) {
            // Menangani jika terjadi kesalahan
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
}
