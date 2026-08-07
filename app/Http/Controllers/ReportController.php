<?php

namespace App\Http\Controllers;

use App\Models\report;
use Illuminate\Http\Request;
use App\Exports\PemakaianBahanExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;


class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax() && $request->has(['tgl_awal', 'tgl_akhir'])) {
            $tglAwal = $request->input('tgl_awal');
            $tglAkhir = $request->input('tgl_akhir');
            $kategori = $request->input('kategori');

            $data = DB::table('npk_planning')
                ->join('bahan', 'npk_planning.id_barang', '=', 'bahan.id')
                ->leftJoin('kategori_bahan', 'bahan.kategori', '=', 'kategori_bahan.katid')
                ->leftJoin('vw_harga_avg_barang', 'bahan.id', '=', 'vw_harga_avg_barang.id')
                ->select(
                    'npk_planning.id_barang',
                    'bahan.nama as nama_bahan',
                    'kategori_bahan.katnama as nama_kategori',
                    'bahan.satuan',
                    DB::raw('IFNULL(vw_harga_avg_barang.average_harga, 0) as harga_satuan'),
                    DB::raw('SUM(npk_planning.jumlah_terkirim) as total_keluar'),
                    DB::raw('SUM(npk_planning.jumlah_terkirim) * IFNULL(vw_harga_avg_barang.average_harga, 0) as total_nominal')
                )
                ->whereBetween('npk_planning.tanggal', [$tglAwal, $tglAkhir])
                ->when($kategori, function ($query, $kategori) {
                    return $query->whereIn('bahan.kategori', is_array($kategori) ? $kategori : [$kategori]);
                })
                ->groupBy('npk_planning.id_barang', 'bahan.nama', 'kategori_bahan.katnama', 'bahan.satuan', 'vw_harga_avg_barang.average_harga')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        }

        $kategoriList = DB::table('kategori_bahan')->select('katid', 'katnama')->get();
        return view('accouting.index', compact('kategoriList'));
    }

    public function exportExcel(Request $request)
    {
        $filters = [
            'tgl_awal' => $request->input('tgl_awal'),
            'tgl_akhir' => $request->input('tgl_akhir'),
            'kategori' => $request->input('kategori')
        ];

        return Excel::download(new PemakaianBahanExport($filters), 'laporan-pemakaian-' . date('Y-m-d') . '.xlsx');
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(report $report)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(report $report)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($request, report $report)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(report $report)
    {
        //
    }
}
