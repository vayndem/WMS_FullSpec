<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminLpbTemporaryController extends Controller
{
    public function index()
    {
        $user = session('user_data');
        return view('qc.temp', compact('user'));
    }

    public function getLpbTemporaryData(Request $request)
    {
        $lpbQuery = DB::table('admin_lpb_temporary')
            ->select(
                'admin_lpb_temporary.*',
                'suppliers.nama as supplier_nama'
            )
            ->leftJoin('inv_po', 'admin_lpb_temporary.no_po', '=', 'inv_po.no_po')
            ->leftJoin('suppliers', 'inv_po.id_suplier', '=', 'suppliers.id');

        if (!empty($request->searchTerm)) {
            $searchTerm = $request->searchTerm;
            $lpbQuery->where(function ($query) use ($searchTerm) {
                $query->where('admin_lpb_temporary.id_lpb', 'like', "%$searchTerm%")
                    ->orWhere('admin_lpb_temporary.no_po', 'like', "%$searchTerm%")
                    ->orWhere('suppliers.nama', 'like', "%$searchTerm%");
            });
        }

        $lpbData = $lpbQuery->orderByDesc('admin_lpb_temporary.created_at')->get();

        return response()->json([
            'data' => $lpbData
        ]);
    }

    public function store(Request $request)
    {
        $user = session('user_data');
        if ($user['type'] != 14) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menambah data!'], 403);
        }
        DB::beginTransaction();
        try {
            $user = session('user_data');
            $tanggalTerima = Carbon::parse($request->tanggalBarangDiterima)->toDateString();
            $yearLpb = Carbon::parse($tanggalTerima)->format('y');

            $idLpb = "TMP" . $yearLpb . str_pad(DB::table('admin_lpb_temporary')->count() + 1, 4, '0', STR_PAD_LEFT);

            DB::table('admin_lpb_temporary')->insert([
                'id_lpb' => $idLpb,
                'tanggal' => $tanggalTerima,
                'no_po' => $request->no_po,
                'no_sj' => $request->nomorSuratJalan ?? 'AUTO-' . now()->timestamp,
                'id_user' => $user['id'],
                'status' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($request->details as $detail) {
                DB::table('admin_lpb_detail_temporary')->insert([
                    'id_lpb' => $idLpb,
                    'id_bahan' => $detail['id_bahan'],
                    'id_kategori' => $detail['katid'],
                    'jumlah_barang_diterima' => $detail['jumlah_barang_diterima'],
                    'lot_number' => $detail['lot_number'],
                    'harga' => $detail['harga'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data disimpan ke antrean QC']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function approve(Request $request)
    {
        $user = session('user_data');
        if ($user['type'] != 12) {
            return response()->json(['success' => false, 'message' => 'Hanya QC yang dapat melakukan ACC!'], 403);
        }

        DB::beginTransaction();
        try {
            $idLpbTemp = $request->id_lpb;
            $tempHeader = DB::table('admin_lpb_temporary')->where('id_lpb', $idLpbTemp)->first();
            $tempDetails = DB::table('admin_lpb_detail_temporary')->where('id_lpb', $idLpbTemp)->get();

            $invPo = DB::table('inv_po')->where('no_po', $tempHeader->no_po)->first();
            $prefix = ($invPo->jenis == 0) ? 'LPBPO' : (($invPo->jenis == 1) ? 'LPBPP' : 'LPBMO');
            $yearLpb = \Carbon\Carbon::parse($tempHeader->tanggal)->format('y');

            $lastLpb = DB::table('admin_lpb')
                ->where('id_lpb', 'like', $prefix . $yearLpb . '%')
                ->orderBy('id_lpb', 'desc')
                ->first();

            $lastNumber = $lastLpb ? (int) substr($lastLpb->id_lpb, -3) : 0;
            $idLpbAsli = $prefix . $yearLpb . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

            DB::table('admin_lpb')->insert([
                'id_lpb' => $idLpbAsli,
                'tanggal' => $tempHeader->tanggal,
                'no_po' => $tempHeader->no_po,
                'no_sj' => $tempHeader->no_sj,
                'id_user' => $tempHeader->id_user,
                'jenis_lpb' => ($invPo->jenis + 1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($tempDetails as $detail) {
                DB::table('admin_lpb_detail')->insert([
                    'id_lpb' => $idLpbAsli,
                    'id_bahan' => $detail->id_bahan,
                    'id_kategori' => $detail->id_kategori,
                    'jumlah_barang_diterima' => $detail->jumlah_barang_diterima,
                    'lot_number' => $detail->lot_number,
                    'harga' => $detail->harga,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('bahan')->where('id', $detail->id_bahan)->update([
                    'stok_onhand' => DB::raw('stok_onhand + ' . $detail->jumlah_barang_diterima),
                    'stok_onpurchase' => DB::raw('GREATEST(0, stok_onpurchase - ' . $detail->jumlah_barang_diterima . ')'),
                    'updated_at' => now(),
                ]);

                DB::table('inv_podetail')->where('no_po', $tempHeader->no_po)
                    ->where('id_bahan', $detail->id_bahan)
                    ->increment('diterima', $detail->jumlah_barang_diterima);
            }

            if ($request->has('catatan') && is_array($request->catatan)) {
                foreach ($request->catatan as $item) {
                    DB::table('catatan_customer')->insert([
                        'id_bahan'          => $item['id_bahan'],
                        'no_po'             => $tempHeader->no_po,
                        'id_lpb'            => $idLpbAsli,
                        'salah_spesifikasi' => $item['salah_spesifikasi'],
                        'jumlah_kurang'     => $item['jumlah_kurang'],
                        'rusak'             => $item['rusak'],
                        'tidak_layak'       => $item['tidak_layak'],
                        'cover_rusak'       => $item['cover_rusak'],
                        'kemasan_bocor'     => $item['kemasan_bocor'],
                        'notes'             => $item['notes'],
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }
            }

            DB::table('inv_po')->where('no_po', $tempHeader->no_po)->update(['status' => 1]);
            DB::table('admin_lpb_temporary')->where('id_lpb', $idLpbTemp)->delete();
            DB::table('admin_lpb_detail_temporary')->where('id_lpb', $idLpbTemp)->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'QC Approved. Stok diperbarui & Catatan tersimpan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
