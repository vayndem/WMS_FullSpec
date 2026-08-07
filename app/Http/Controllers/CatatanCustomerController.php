<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatatanCustomerController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user_data');
        $query = DB::table('catatan_customer as cc')
            ->join('bahan as b', 'cc.id_bahan', '=', 'b.id')
            ->join('admin_lpb as lpb', 'cc.id_lpb', '=', 'lpb.id_lpb')
            ->select(
                'cc.*',
                'b.nama as nama_bahan',
                'lpb.tanggal as tanggal_terima'
            );
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('lpb.tanggal', [$request->start_date, $request->end_date]);
        }
        if ($request->filled('no_po')) {
            $query->where('cc.no_po', 'like', '%' . $request->no_po . '%');
        }
        $data = $query->orderBy('cc.created_at', 'desc')->get();
        if ($request->ajax()) {
            return response()->json(['data' => $data]);
        }
        return view('qc.catatan', compact('data', 'user'));
    }


    public function show($id)
    {
        $data = DB::table('catatan_customer')->where('id', $id)->first();
        return response()->json($data);
    }

    public function cetakLpbQc($id_lpb)
    {
        $lpb = DB::table('admin_lpb_temporary as lpb')
            ->join('inv_po as po', 'lpb.no_po', '=', 'po.no_po')
            ->join('suppliers as s', 'po.id_suplier', '=', 's.id')
            ->select('lpb.*', 's.nama as nama_supplier')
            ->where('lpb.id_lpb', $id_lpb)
            ->first();
        if (!$lpb) {
            return abort(404);
        }
        $details = DB::table('admin_lpb_detail_temporary as d')
            ->join('bahan as b', 'd.id_bahan', '=', 'b.id')
            ->leftJoin('catatan_customer as cc', function ($join) use ($id_lpb) {
                $join->on('d.id_bahan', '=', 'cc.id_bahan')
                    ->where('cc.id_lpb', '=', $id_lpb);
            })
            ->where('d.id_lpb', $id_lpb)
            ->select(
                'b.nama as nama_barang',
                'd.jumlah_barang_diterima',
                'd.lot_number'
            )
            ->get();

        $nama_barang = $details->pluck('nama_barang')->unique()->implode('<br>');
        $total_jumlah = $details->sum('jumlah_barang_diterima');

        $logoUrl = asset('img/logomuliaoffset.png');
        $logoBase64 = '';
        try {
            $logoData = file_get_contents($logoUrl);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        } catch (\Exception $e) {
            $logoBase64 = $logoUrl;
        }

        $data = [
            'lpb' => $lpb,
            'details' => $details,
            'supplier' => $lpb->nama_supplier,
            'logo_left' => $logoBase64,
            'admin' => session('user_data')['name'],
            'tanggal_cetak' => date('d-M-Y H:i'),
            'no_dokumen' => 'MO-GBB-FR-001',
            'revisi' => '-',
            'tgl_berlaku' => '19 Agustus 2025',
            'nama_barang' => $nama_barang,
            'tanggal_permintaan' => date('d-M-y'),
            'total_palet' => $total_jumlah
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.lpb_temporary', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('LPB_QC_' . $id_lpb . '.pdf');
    }
}
