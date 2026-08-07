<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLpbDetailTemporaryController extends Controller
{
    public function show(Request $request)
    {
        $id_lpb = $request->id_lpb;
        $data = DB::table('admin_lpb_detail_temporary')
            ->join('bahan', 'admin_lpb_detail_temporary.id_bahan', '=', 'bahan.id')
            ->join('kategori_bahan', 'admin_lpb_detail_temporary.id_kategori', '=', 'kategori_bahan.katid')
            ->where('admin_lpb_detail_temporary.id_lpb', $id_lpb)
            ->select(
                'admin_lpb_detail_temporary.*',
                'bahan.nama as nama',
                'bahan.satuan',
                'kategori_bahan.katnama'
            )
            ->get();

        return response()->json(['data' => $data]);
    }

    public function update(Request $request)
    {
        try {
            DB::table('admin_lpb_detail_temporary')
                ->where('id', $request->id)
                ->update([
                    'jumlah_barang_diterima' => $request->qty,
                    'lot_number' => $request->lot,
                    'updated_at' => now()
                ]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        try {
            DB::table('admin_lpb_detail_temporary')->where('id', $id)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
