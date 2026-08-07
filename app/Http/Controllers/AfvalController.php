<?php

namespace App\Http\Controllers;

use App\Models\Afval;
use App\Models\AfvalDetail;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AfvalController extends Controller
{
    public function index()
    {
        $user = session('user_data');
        $title = 'Afval List Transaction';
        return view('afval.index', compact('user', 'title'));
    }

    public function readafval(Request $request)
    {
        if ($request->ajax()) {
            $status = $request->input('status', 'waiting');

            $data = Afval::query()->join('afval_detail', 'afval.kode_afval', '=', 'afval_detail.kode_afval')->select('afval.kode_afval', 'afval.nama', 'afval.alamat', 'afval.tanggal', 'afval.notes', 'afval.status_faktur', DB::raw('GROUP_CONCAT(afval_detail.tipe SEPARATOR " + ") as tipe'), DB::raw('SUM(afval_detail.berat) as berat'), DB::raw('SUM(afval_detail.harga_satuan * afval_detail.berat) as harga_satuan'))->where('afval.status_faktur', $status)->groupBy('afval.kode_afval', 'afval.nama', 'afval.alamat', 'afval.tanggal', 'afval.notes', 'afval.status_faktur');

            return Datatables::of($data)->addIndexColumn()->make(true);
        }
    }

    public function getDetailsafval($kode_afval)
    {
        $details = AfvalDetail::where('kode_afval', $kode_afval)->get();

        if ($details->isEmpty()) {
            return response()->json(['message' => 'Details not found'], 404);
        }

        return response()->json($details);
    }

    public function createafval(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'notes' => 'nullable|string',
            'status_faktur' => 'required|string|max:50',
            'details' => 'required|array|min:1',
            'details.*.tipe' => 'required|string|max:255',
            'details.*.berat' => 'required|numeric|min:0',
            'details.*.harga_satuan' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $prefix = 'AF' . date('d') . date('m');
            $lastData = DB::table('afval')
                ->where('kode_afval', 'like', $prefix . '%')
                ->orderBy('kode_afval', 'desc')
                ->first();

            if ($lastData) {
                $lastNumber = (int) substr($lastData->kode_afval, -3);
                $nextNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $nextNumber = '001';
            }
            $newKodeAfval = $prefix . $nextNumber;

            $afval = Afval::create([
                'kode_afval' => $newKodeAfval,
                'nama' => $request->nama,
                'alamat' => $request->alamat,
                'tanggal' => $request->tanggal,
                'notes' => $request->notes,
                'status_faktur' => $request->status_faktur,
            ]);

            foreach ($request->details as $item) {
                AfvalDetail::create([
                    'kode_afval' => $newKodeAfval,
                    'tipe' => $item['tipe'],
                    'berat' => $item['berat'],
                    'harga_satuan' => $item['harga_satuan'],
                ]);
            }

            DB::commit();

            return response()->json(
                [
                    'success' => true,
                    'message' => 'Data Afval berhasil ditambahkan!',
                    'data' => $afval,
                ],
                201,
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal menyimpan data. Terjadi kesalahan.',
                    'error' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function detailAfvalid($kode_afval)
    {
        $afvalData = Afval::with('details')->where('kode_afval', $kode_afval)->firstOrFail();

        return response()->json($afvalData);
    }

    public function getafvalid()
    {
        $afvalData = Afval::where('status_faktur', 'waiting')->orderBy('kode_afval', 'asc')->select('kode_afval', 'tanggal')->get();
        return response()->json($afvalData);
    }

    public function updateStatusAfval($kode_afval)
    {
        try {
            $afval = Afval::where('kode_afval', $kode_afval)->firstOrFail();
            $afval->status_faktur = 'done faktur';
            $afval->save();

            return response()->json([
                'success' => true,
                'message' => 'Status Afval dengan kode ' . $kode_afval . ' berhasil diubah.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Data Afval dengan kode ' . $kode_afval . ' tidak ditemukan.',
                ],
                404,
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat mengubah status: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }
}
