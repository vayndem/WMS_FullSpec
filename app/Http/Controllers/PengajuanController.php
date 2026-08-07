<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use App\Models\PengajuanDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;

class PengajuanController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user_data');
        if (!$user) {
            abort(403, 'Sesi berakhir, silakan login kembali.');
        }

        $bulan = $request->query('bulan');
        $tahun = $request->query('tahun');

        $query = Pengajuan::with(['details.bahan']);

        if ($user['type'] != 5) {
            $query->where('user_id', $user['id']);
        }

        $pengajuan = $query->when($bulan, function ($q) use ($bulan) {
            return $q->whereMonth('tanggal', $bulan);
        })
            ->when($tahun, function ($q) use ($tahun) {
                return $q->whereYear('tanggal', $tahun);
            })
            ->orderBy('id', 'desc')
            ->get();

        return view('pengajuan.index', compact('pengajuan', 'user'));
    }

    public function show($id)
    {
        $pengajuan = Pengajuan::with(['details.bahan', 'suplier'])->find($id);
        if (!$pengajuan) return response()->json(['success' => false], 404);

        $pengajuan->nama_suplier_asli = $pengajuan->suplier->nama ?? '-';

        $pengajuan->details->transform(function ($item) {
            $item->nama_bahan_asli = $item->bahan ? $item->bahan->nama : $item->id_bahan;
            return $item;
        });

        return response()->json(['success' => true, 'data' => $pengajuan]);
    }

    public function store(Request $request)
    {
        $user = session('user_data');
        DB::beginTransaction();
        try {
            $prefix = 'PGJ-NK-';
            $tahunBulan = Carbon::now()->format('ym');
            $lastOrder = Pengajuan::where('no_order', 'like', $prefix . $tahunBulan . '%')
                ->orderBy('no_order', 'desc')
                ->first();

            $newIncrement = $lastOrder ? ((int) substr($lastOrder->no_order, -3)) + 1 : 1;
            $generatedNoOrder = $prefix . $tahunBulan . str_pad($newIncrement, 3, '0', STR_PAD_LEFT);
            $isPurchasing = ($user['type'] == 5);
            $pengajuan = Pengajuan::create([
                'tanggal'             => $request->tanggal,
                'no_order'            => $generatedNoOrder,
                'notes'               => $request->notes ?? '-',
                'user_id'             => $user['id'],
                'status'              => 0,
                'id_suplier'          => $isPurchasing ? ($request->id_suplier ?? 0) : 0,
                'untukperhatian'      => $isPurchasing ? ($request->untukperhatian ?? '-') : '-',
                'term'                => $isPurchasing ? ($request->term ?? '-') : '-',
                'ppn'                 => $isPurchasing ? ($request->ppn ?? 0) : 0,
                'totalexclude'        => $isPurchasing ? ($request->totalexclude ?? 0) : 0,
                'totalppn'            => $isPurchasing ? ($request->totalppn ?? 0) : 0,
                'totalinclude'        => $isPurchasing ? ($request->totalinclude ?? 0) : 0,
                'diskon'              => $isPurchasing ? ($request->diskon ?? 0) : 0,
                'ongkir'              => $isPurchasing ? ($request->ongkir ?? 0) : 0,
                'GrandTotalPembelian' => $isPurchasing ? ($request->GrandTotalPembelian ?? 0) : 0,
                'tanggal_dipesan'     => Carbon::now()->format('Y-m-d'),
                'tanggal_diproses'    => null,
            ]);

            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    PengajuanDetail::create([
                        'pengajuan_id' => $pengajuan->id,
                        'id_bahan'     => $item['id_bahan'],
                        'jumlah'       => $item['jumlah'],
                        'harga'        => $isPurchasing ? ($item['harga'] ?? 0) : 0,
                        'exclude'      => $isPurchasing ? ($item['exclude'] ?? 0) : 0,
                        'ppn'          => $isPurchasing ? ($item['ppn'] ?? 0) : 0,
                        'include'      => $isPurchasing ? ($item['include'] ?? 0) : 0,
                        'diterima'     => 0,
                        'jenis'        => 0,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = session('user_data');
        DB::beginTransaction();
        try {
            $pengajuan = Pengajuan::findOrFail($id);
            $isPurchasing = ($user['type'] == 5);

            $dataHeader = $request->except(['items', 'id']);

            if ($isPurchasing) {
                $dataHeader['tanggal_diproses'] = $request->tanggal_diproses ?? Carbon::now()->toDateString();
                $dataHeader['status'] = 1;
            } else {
                $dataHeader['status'] = 0;
                $dataHeader['id_suplier'] = 0;
                $dataHeader['totalinclude'] = 0;
                $dataHeader['GrandTotalPembelian'] = 0;
            }

            $pengajuan->update($dataHeader);

            if ($request->has('items')) {
                PengajuanDetail::where('pengajuan_id', $id)->delete();
                foreach ($request->items as $item) {
                    PengajuanDetail::create([
                        'pengajuan_id' => $id,
                        'id_bahan'     => $item['id_bahan'],
                        'jumlah'       => $item['jumlah'],
                        'harga'        => $isPurchasing ? ($item['harga'] ?? 0) : 0,
                        'exclude'      => $isPurchasing ? ($item['exclude'] ?? 0) : 0,
                        'ppn'          => $isPurchasing ? ($item['ppn'] ?? 0) : 0,
                        'include'      => $isPurchasing ? ($item['include'] ?? 0) : 0,
                        'diterima'     => 0,
                        'jenis'        => 0,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            PengajuanDetail::where('pengajuan_id', $id)->delete();
            Pengajuan::destroy($id);
            DB::commit();
            return response()->json(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false], 500);
        }
    }

    public function updatestatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:pengajuan,id',
            'status' => 'required|in:1,2',
            'keterangan' => 'required_if:status,1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false], 422);
        }

        try {
            $pengajuan = Pengajuan::findOrFail($request->id);
            $pengajuan->update([
                'status' => $request->status,
                'keterangan' => $request->status == 1 ? $request->keterangan : null,
            ]);
            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    public function lihatCetak(Request $request)
    {
        $pengajuan = Pengajuan::with(['details.bahan', 'suplier'])->find($request->nomorpo);

        if (!$pengajuan) {
            abort(404, 'Data pengajuan tidak ditemukan.');
        }

        $pengajuan->details->transform(function ($item) {
            $item->nama_bahan_asli = $item->bahan ? $item->bahan->nama : $item->id_bahan;
            return $item;
        });

        $pdf = Pdf::loadView('pengajuan.preview', compact('pengajuan'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('Pengajuan-' . $pengajuan->no_order . '.pdf');
    }

    //* API Methods
    public function apiStoreExternal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.id_bahan' => 'required',
            'items.*.jumlah' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $prefix = 'PGJ-NK-';
            $tahunBulan = date('ym');
            $lastOrder = Pengajuan::where('no_order', 'like', $prefix . $tahunBulan . '%')
                ->orderBy('no_order', 'desc')
                ->first();

            $newIncrement = $lastOrder ? ((int) substr($lastOrder->no_order, -3)) + 1 : 1;
            $generatedNoOrder = $prefix . $tahunBulan . str_pad($newIncrement, 3, '0', STR_PAD_LEFT);

            $pengajuan = Pengajuan::create([
                'tanggal' => $request->tanggal ?? date('Y-m-d'),
                'no_order' => $generatedNoOrder,
                'notes' => '[PPC] ' . ($request->notes ?? '-'),
                'user_id' => $request->user()->id,
                'status' => 0,
                'id_suplier' => 0,
                'untukperhatian' => '-',
                'term' => '-',
                'ppn' => 0,
                'totalexclude' => 0,
                'totalppn' => 0,
                'totalinclude' => 0,
                'diskon' => 0,
                'ongkir' => 0,
                'GrandTotalPembelian' => 0,
                'tanggal_dipesan' => date('Y-m-d'),
                'tanggal_diproses' => null,
            ]);

            foreach ($request->items as $item) {
                PengajuanDetail::create([
                    'pengajuan_id' => $pengajuan->id,
                    'id_bahan' => $item['id_bahan'],
                    'jumlah' => $item['jumlah'],
                    'harga' => 0,
                    'exclude' => 0,
                    'ppn' => 0,
                    'include' => 0,
                    'diterima' => 0,
                    'jenis' => 0,
                ]);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $pengajuan->id,
                    'no_order' => $generatedNoOrder
                ]
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function apiCheckStatus($id)
    {
        $pengajuan = Pengajuan::find($id);

        if (!$pengajuan) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pengajuan->id,
                'no_order' => $pengajuan->no_order,
                'status' => $pengajuan->status,
                'keterangan' => $pengajuan->keterangan,
                'total' => $pengajuan->totalinclude,
                'tanggal_proses' => $pengajuan->tanggal_diproses
            ]
        ]);
    }
    public function apiListExternal(Request $request)
    {
        try {
            $bulan = $request->query('bulan');
            $tahun = $request->query('tahun');
            $query = Pengajuan::with(['details.bahan'])
                ->where('user_id', $request->user()->id);

            $pengajuan = $query->when($bulan, function ($q) use ($bulan) {
                return $q->whereMonth('tanggal', $bulan);
            })
                ->when($tahun, function ($q) use ($tahun) {
                    return $q->whereYear('tanggal', $tahun);
                })
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar riwayat pengajuan berhasil diambil',
                'data' => $pengajuan
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function apiShowDetail($id, Request $request)
    {
        try {
            $pengajuan = Pengajuan::with(['details.bahan'])
                ->where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$pengajuan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data detail tidak ditemukan atau Anda tidak memiliki akses.'
                ], 404);
            }
            $details = $pengajuan->details->map(function ($item) {
                return [
                    'id_bahan' => $item->id_bahan,
                    'nama_bahan' => $item->bahan ? $item->bahan->nama : $item->id_bahan,
                    'satuan' => $item->bahan ? $item->bahan->satuan : '-',
                    'jumlah' => $item->jumlah,
                    'harga' => $item->harga,
                    'total' => $item->include,
                ];
            });

            return response()->json([
                'success' => true,
                'header' => [
                    'no_order' => $pengajuan->no_order,
                    'tanggal' => $pengajuan->tanggal,
                    'notes' => $pengajuan->notes,
                    'status' => $pengajuan->status,
                    'keterangan_purchasing' => $pengajuan->keterangan
                ],
                'data' => $details
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
