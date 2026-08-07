<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Exports\StockOpnameDetailExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use TCPDF;

class GudangController extends Controller
{
    public function lpb()
    {
        $user = session('user_data');
        return view('gudang.lpb', compact('user'));
    }

    public function getLpbData(Request $request)
    {
        $user = session('user_data');
        $lpbQuery = DB::table('admin_lpb')
            ->select(
                'admin_lpb.id_lpb',
                'admin_lpb.tanggal',
                'admin_lpb.no_po',
                'admin_lpb.no_sj',
                'admin_lpb.kunci',
                'inv_po.id_suplier',
                'suppliers.nama as supplier_nama',
                'suppliers.alamat as supplier_alamat'
            )
            ->leftJoin('inv_po', 'admin_lpb.no_po', '=', 'inv_po.no_po')
            ->leftJoin('suppliers', 'inv_po.id_suplier', '=', 'suppliers.id')
            ->where('admin_lpb.flag', 0);

        if (!empty($request->filterMonthYear)) {
            $filterMonthYear = explode('-', $request->filterMonthYear);
            $filterYear = $filterMonthYear[0];
            $filterMonth = $filterMonthYear[1];

            $lpbQuery->whereYear('admin_lpb.created_at', $filterYear)
                ->whereMonth('admin_lpb.created_at', $filterMonth);
        }

        $allowedTypes = isset($user['type']) && $user['type'] == 5 ? [1, 2, 3] : [1, 2];

        if (!empty($request->filterLpbType) && $request->filterLpbType != 'all') {
            $lpbQuery->where('admin_lpb.id_lpb', 'like', $request->filterLpbType . '%')
                ->whereIn('admin_lpb.jenis_lpb', $allowedTypes);
        } else {
            $lpbQuery->whereIn('admin_lpb.jenis_lpb', $allowedTypes);
        }

        if (!empty($request->searchTerm)) {
            $searchTerm = $request->searchTerm;
            $lpbQuery->where(function ($query) use ($searchTerm) {
                $query->where('admin_lpb.id_lpb', 'like', "%$searchTerm%")
                    ->orWhere('admin_lpb.no_po', 'like', "%$searchTerm%")
                    ->orWhere('admin_lpb.no_sj', 'like', "%$searchTerm%")
                    ->orWhere('suppliers.nama', 'like', "%$searchTerm%");
            });
        }

        $filteredRecordsCount = $lpbQuery->count();
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;

        if ($request->has('order') && !empty($request->input('order.0.column'))) {
            $orderColumnIndex = $request->input('order.0.column');
            $orderDir = $request->input('order.0.dir');
            $orderColumnName = $request->input("columns.{$orderColumnIndex}.data");

            $columnMap = [
                'id_lpb' => 'admin_lpb.id_lpb',
                'tanggal' => 'admin_lpb.tanggal',
                'supplier_nama' => 'suppliers.nama',
                'no_po' => 'admin_lpb.no_po',
                'no_sj' => 'admin_lpb.no_sj',
            ];

            if (isset($columnMap[$orderColumnName])) {
                $lpbQuery->orderBy($columnMap[$orderColumnName], $orderDir);
            } else {
                $lpbQuery->orderByDesc('admin_lpb.created_at');
            }
        } else {
            $lpbQuery->orderByDesc('admin_lpb.created_at');
        }

        $lpbData = $lpbQuery->skip($start)->take($length)->get();

        $totalRecords = DB::table('admin_lpb')
            ->where('flag', 0)
            ->whereIn('jenis_lpb', $allowedTypes)
            ->count();

        $data = $lpbData->map(function ($item, $index) use ($start) {
            $actionsButton = $item->kunci == 1
                ? '<button class="btn btn-secondary btn-sm" disabled title="LPB sudah dikunci"><i class="fas fa-lock"></i> Tidak Bisa Cetak</button>'
                : '<button class="btn btn-primary btn-sm btn-cetak" data-id="' . $item->id_lpb . '" title="Cetak LPB"><i class="far fa-file-pdf"></i> Cetak</button>';

            return [
                'no' => $start + $index + 1,
                'id_lpb' => $item->id_lpb,
                'tanggal' => $item->tanggal,
                'no_po' => $item->no_po,
                'no_sj' => $item->no_sj,
                'supplier_nama' => $item->supplier_nama,
                'supplier_alamat' => $item->supplier_alamat,
                'actions' => $actionsButton,
                'kunci' => $item->kunci,
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecordsCount,
            'data' => $data,
        ]);
    }

    public function updateLpbData(Request $request)
    {
        $request->validate([
            'id_lpb' => 'required|exists:admin_lpb,id_lpb',
            'type' => 'required|in:tanggal,no_sj',
            'value' => 'required',
        ]);

        $id_lpb = $request->id_lpb;
        $type = $request->type;
        $value = $request->value;

        $lpb = DB::table('admin_lpb')->where('id_lpb', $id_lpb)->first();

        if (!$lpb) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan!'], 404);
        }

        $updateData = [];
        if ($type == 'tanggal') {
            $updateData['tanggal'] = $value;
        } elseif ($type == 'no_sj') {
            $updateData['no_sj'] = $value;
        }

        if ($lpb->kunci != 0 || $lpb->ulang != 0 || $lpb->cetakan != 0) {
            $updateData['cetak_ulang'] = 1;
        }

        if (!empty($updateData)) {
            DB::table('admin_lpb')->where('id_lpb', $id_lpb)->update($updateData);
        }

        return response()->json(['success' => true]);
    }

    public function getDetailLpb(Request $request)
    {
        $id_lpb = $request->input('id_lpb');

        if (empty($id_lpb)) {
            return response()->json(['error' => 'Parameter id_lpb diperlukan'], 400);
        }

        $query = DB::table('admin_lpb_detail')->join('bahan', 'admin_lpb_detail.id_bahan', '=', 'bahan.id')->join('kategori_bahan', 'bahan.kategori', '=', 'kategori_bahan.katid')->where('admin_lpb_detail.id_lpb', $id_lpb)->select('bahan.nama as nama', 'bahan.keterangan_bahan as keterangan_bahan', 'kategori_bahan.katnama as katnama', 'bahan.satuan as satuan', 'admin_lpb_detail.jumlah_barang_diterima as jumlah_barang_diterima', 'admin_lpb_detail.lot_number as lot_number', 'admin_lpb_detail.id as id_lpb_detail');

        $totalData = $query->count();

        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('bahan.nama', 'LIKE', "%{$search}%")
                    ->orWhere('bahan.keterangan_bahan', 'LIKE', "%{$search}%")
                    ->orWhere('kategori_bahan.katnama', 'LIKE', "%{$search}%")
                    ->orWhere('bahan.satuan', 'LIKE', "%{$search}%")
                    ->orWhere('admin_lpb_detail.lot_number', 'LIKE', "%{$search}%");
            });
        }
        $totalFiltered = $query->count();
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $data = $query->offset($start)->limit($length)->orderBy('admin_lpb_detail.id', 'asc')->get();
        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $data,
        ]);
    }

    public function updateLpbDetail(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'id' => 'required|exists:admin_lpb_detail,id',
                'column' => 'required|in:9,10',
                'new_value' => 'required',
            ]);

            $id = $validated['id'];
            $column = $validated['column'];
            $newValue = $validated['new_value'];
            $lpbDetail = DB::table('admin_lpb_detail')->where('id', $id)->first();

            if (!$lpbDetail) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.']);
            }

            if ($column == 9) {
                $oldValue = $lpbDetail->jumlah_barang_diterima;
                $idBahan = $lpbDetail->id_bahan;
                DB::table('admin_lpb_detail')
                    ->where('id', $id)
                    ->update(['jumlah_barang_diterima' => $newValue]);
                $difference = $newValue - $oldValue;
                $stokOnPurchase = DB::table('bahan')->where('id', $idBahan)->value('stok_onpurchase');
                $stokOnPurchaseAfter = $stokOnPurchase - $difference;
                if ($stokOnPurchaseAfter < 0) {
                    $difference = $stokOnPurchase;
                }

                DB::table('bahan')
                    ->where('id', $idBahan)
                    ->update([
                        'stok_onhand' => DB::raw("stok_onhand + $difference"),
                        'stok_onpurchase' => DB::raw("stok_onpurchase - $difference"),
                        'updated_at' => now(),
                    ]);
            } elseif ($column == 10) {
                DB::table('admin_lpb_detail')
                    ->where('id', $id)
                    ->update(['lot_number' => $newValue]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui dan stok diperbarui.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function deleteLpbDetail(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'id' => 'required|exists:admin_lpb_detail,id',
            ]);

            $id = $validated['id'];
            $lpbDetail = DB::table('admin_lpb_detail')->where('id', $id)->first();
            if (!$lpbDetail) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.']);
            }
            $idBahan = $lpbDetail->id_bahan;
            $jumlahBarangDiterima = $lpbDetail->jumlah_barang_diterima;
            $deleted = DB::table('admin_lpb_detail')->where('id', $id)->delete();
            if ($deleted) {
                DB::table('bahan')
                    ->where('id', $idBahan)
                    ->update([
                        'stok_onhand' => DB::raw("stok_onhand - $jumlahBarangDiterima"),
                        'stok_onpurchase' => DB::raw("stok_onpurchase + $jumlahBarangDiterima"),
                        'updated_at' => now(),
                    ]);

                DB::commit();
                return response()->json(['success' => true, 'message' => 'Detail berhasil dihapus dan stok diperbarui.']);
            } else {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Gagal menghapus detail.']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function getSuppliers()
    {
        $user = session('user_data');

        $operator = $user['type'] == '5' ? '=' : '!=';

        $suppliers = DB::table('suppliers')->join('inv_po', 'suppliers.id', '=', 'inv_po.id_suplier')->where('inv_po.status', '!=', 2)->where('inv_po.jenis', $operator, '2')->select('suppliers.id as id_supplier', 'suppliers.nama')->distinct()->get();

        return response()->json(['suppliers' => $suppliers]);
    }

    public function getNoPoBySupplier(Request $request)
    {
        $details = DB::table('inv_po')
            ->where('inv_po.status', '!=', 2)
            ->where('inv_po.id_suplier', $request->id_supplier)
            ->select(['inv_po.no_po', 'inv_po.tanggal', 'inv_po.no_order'])
            ->groupBy('inv_po.no_po', 'inv_po.tanggal', 'inv_po.no_order')
            ->orderBy('inv_po.tanggal', 'desc')
            ->get();

        return response()->json(['no_po' => $details]);
    }

    public function getDetailByNoPo(Request $request)
    {
        $details = DB::table('inv_podetail')
            ->join('bahan', 'inv_podetail.id_bahan', '=', 'bahan.id')
            ->join('kategori_bahan', 'bahan.kategori', '=', 'kategori_bahan.katid')
            ->where('inv_podetail.no_po', $request->no_po)
            ->select(['inv_podetail.no_po', 'bahan.nama as nama_barang', 'inv_podetail.harga', 'inv_podetail.jumlah', 'bahan.satuan', 'kategori_bahan.katnama as kategori', 'inv_podetail.id_bahan', 'kategori_bahan.katid'])
            ->get();

        return response()->json(['details' => $details]);
    }

    public function saveLpb(Request $request)
    {
        try {
            $user = session('user_data');
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'User is not authenticated']);
            }

            $tanggalTerima = Carbon::parse($request->tanggalBarangDiterima)->toDateString();
            $yearLpb = Carbon::parse($tanggalTerima)->format('y'); // <-- tahun berdasarkan tanggal barang diterima

            $idUser = $user['id'];
            DB::beginTransaction();
            $invPo = DB::table('inv_po')->where('no_po', $request->no_po)->first();

            if (!$invPo) {
                return response()->json(['success' => false, 'message' => 'Data PO tidak ditemukan!']);
            }

            if ($invPo->jenis == 0) {
                $prefix = 'LPBPO';
                $jenisLpb = 1;
                $suratJalanPrefix = 'MOPO';
            } elseif ($invPo->jenis == 1) {
                $prefix = 'LPBPP';
                $jenisLpb = 2;
                $suratJalanPrefix = 'MOPP';
            } elseif ($invPo->jenis == 2) {
                $prefix = 'LPBMO';
                $jenisLpb = 3;
                $suratJalanPrefix = 'NONE';
            }

            // $currentYear = date('y');
            $lastLpb = DB::table('admin_lpb')
                // ->where('id_lpb', 'like', $prefix . $currentYear . '%')
                ->where('id_lpb', 'like', $prefix . $yearLpb . '%')
                ->orderBy('id_lpb', 'desc')
                ->first();
            $lastNumber = $lastLpb ? (int) substr($lastLpb->id_lpb, -3) : 0;
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            // $idLpb = $prefix . $currentYear . $newNumber;
            $idLpb      = $prefix . $yearLpb . $newNumber;

            $nomorSuratJalan = $request->nomorSuratJalan;
            if (empty($nomorSuratJalan)) {
                if ($invPo->jenis == 0) {
                    $suratJalanPrefix = 'MOPO';
                } elseif ($invPo->jenis == 1) {
                    $suratJalanPrefix = 'MOPP';
                } elseif ($invPo->jenis == 2) {
                    $suratJalanPrefix = 'NONE';
                }
                $nomorSuratJalan =
                    $suratJalanPrefix .
                    // $currentYear .
                    $yearLpb .
                    str_pad(
                        DB::table('admin_lpb')
                            // ->where('no_sj', 'like', $suratJalanPrefix . $currentYear . '%')
                            ->where('no_sj', 'like', $suratJalanPrefix . $yearLpb . '%')
                            ->count() + 1,
                        4,
                        '0',
                        STR_PAD_LEFT,
                    );
            } else {
                $existingSj = DB::table('admin_lpb')->where('no_sj', $nomorSuratJalan)->first();
                if ($existingSj) {
                    return response()->json(['success' => false, 'message' => 'Nomor Surat Jalan sudah dipakai!']);
                }
            }
            DB::table('admin_lpb')->insert([
                'id_lpb' => $idLpb,
                'tanggal' => $request->tanggalBarangDiterima,
                'no_po' => $request->no_po,
                'no_sj' => $nomorSuratJalan,
                'id_user' => $idUser,
                'jenis_lpb' => $jenisLpb,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($request->details as $detail) {
                $jumlahBarangDiterima = $detail['jumlah_barang_diterima'];

                DB::table('admin_lpb_detail')->insert([
                    'id_lpb' => $idLpb,
                    'id_bahan' => $detail['id_bahan'],
                    'id_kategori' => $detail['katid'],
                    'jumlah_barang_diterima' => $jumlahBarangDiterima,
                    'lot_number' => $detail['lot_number'],
                    'harga' => $detail['harga'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('bahan')
                    ->where('id', $detail['id_bahan'])
                    ->update([
                        'stok_onhand' => DB::raw('stok_onhand + ' . $jumlahBarangDiterima),
                        'stok_onpurchase' => DB::raw('GREATEST(0, stok_onpurchase - ' . $jumlahBarangDiterima . ')'),
                        'updated_at' => now(),
                    ]);

                DB::table('inv_podetail')->where('no_po', $request->no_po)->where('id_bahan', $detail['id_bahan'])->increment('diterima', $jumlahBarangDiterima);
            }

            DB::table('inv_po')
                ->where('no_po', $request->no_po)
                ->update(['status' => 1]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'LPB berhasil disimpan dan status berhasil diperbarui']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function cetakLpb(Request $request)
    {
        $id_lpb = $request->input('id_lpb');
        $lpb = DB::table('admin_lpb')
            ->leftJoin('inv_po', 'admin_lpb.no_po', '=', 'inv_po.no_po')
            ->leftJoin('suppliers', 'inv_po.id_suplier', '=', 'suppliers.id')
            ->where('admin_lpb.id_lpb', $id_lpb)
            ->select('admin_lpb.id_lpb', 'admin_lpb.no_po', 'admin_lpb.no_sj', 'admin_lpb.tanggal', 'admin_lpb.cetak_ulang', 'admin_lpb.kunci', 'admin_lpb.cetakan', 'admin_lpb.ulang', 'suppliers.nama as supplier_nama', 'suppliers.alamat as supplier_alamat')
            ->first();

        if (!$lpb) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan!'], 404);
        }

        $updateData = [];
        if ($lpb->cetak_ulang == 0) {
            $updateData = [
                'kunci' => 1,
                'ulang' => DB::raw('ulang + 1'),
            ];
        } else {
            $updateData = [
                'kunci' => 1,
                'cetakan' => DB::raw('cetakan + 1'),
                'ulang' => 0,
            ];
        }
        DB::table('admin_lpb')->where('id_lpb', $id_lpb)->update($updateData);

        $details = DB::table('admin_lpb_detail')
            ->join('bahan', 'admin_lpb_detail.id_bahan', '=', 'bahan.id')
            ->join('kategori_bahan', 'bahan.kategori', '=', 'kategori_bahan.katid')
            ->where('admin_lpb_detail.id_lpb', $id_lpb)
            ->select('bahan.nama', 'admin_lpb_detail.jumlah_barang_diterima', 'kategori_bahan.katnama', 'bahan.satuan')
            ->get();

        $pdf = new MyPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $footerText = 'Cetakan ke ' . ($lpb->cetakan + 1) . ' dan duplikasi ke ' . ($lpb->ulang ?? '0');
        $pdf->setFooterText($footerText);
        $pdf->SetTitle('Laporan Penerimaan Barang');
        $pdf->setPrintHeader(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 8);

        $html = '<h2 align="center">Laporan Penerimaan Barang</h2>';
        $html .= '<table cellpadding="5" width="100%" style="border: 1px solid black; border-collapse: collapse; margin-bottom: 20px;">
                <tr>
                    <td width="50%"><strong>LPB No:</strong> ' . $lpb->id_lpb . '</td>
                    <td width="50%"><strong>PO No:</strong> ' . $lpb->no_po . '</td>
                </tr>
                <tr>
                    <td width="50%"><strong>Di Terima Dari:</strong> ' . $lpb->supplier_nama . '</td>
                    <td width="50%"><strong>No Surat Pengantar:</strong> ' . $lpb->no_sj . '</td>
                </tr>
                <tr>
                    <td width="50%"><strong>Alamat Supplier:</strong> ' . $lpb->supplier_alamat . '</td>
                    <td width="50%"><strong>Tanggal:</strong> ' . date('d M Y', strtotime($lpb->tanggal)) . '</td>
                </tr>
            </table>';

        $html .= '<br><br>';
        $html .= '<table border="1" cellpadding="5" width="100%" style="border-collapse: collapse; margin-top: 20px;">
                <thead>
                    <tr style="background-color:#f0f0f0;">
                        <th width="5%" align="center">No</th>
                        <th width="50%" align="center">Nama Barang</th>
                        <th width="30%" align="center">Kategori</th>
                        <th width="15%" align="center">Jumlah Diterima</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($details as $index => $detail) {
            $html .= '<tr>
                    <td width="5%" align="center">' . ($index + 1) . '</td>
                    <td width="50%">' . $detail->nama . '</td>
                    <td width="30%">' . $detail->katnama . '</td>
                    <td width="15%" align="right">' .
                (fmod($detail->jumlah_barang_diterima, 1) != 0 ? number_format($detail->jumlah_barang_diterima, 2, '.', ',') : number_format($detail->jumlah_barang_diterima, 0, '.', ',')) .
                ' ' . $detail->satuan . '</td>
                </tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<br><br><table width="100%">
                <tr>
                    <td width="55%"></td>
                    <td align="center">Semarang, ' . date('d M Y', strtotime($lpb->tanggal)) . '<br><br>Barang telah diterima dengan baik,<br><br><br><br><br>(...............................................)</td>
                </tr>
            </table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdfContent = $pdf->Output('Laporan_Penerimaan_Barang_' . $id_lpb . '.pdf', 'S');

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Laporan_Penerimaan_Barang_' . $id_lpb . '.pdf"');
    }
    public function stokAwal()
    {
        $data = [
            'title' => 'Stock Awal',
        ];
        $user = session('user_data');
        return view('gudang.stokawal', compact('user', 'data'));
    }

    public function getStockAwalData(Request $request)
    {
        if ($request->ajax()) {
            $lpbData = DB::table('admin_lpb')->select('admin_lpb.id as id', 'admin_lpb.id_lpb', 'admin_lpb.tanggal', 'admin_lpb.no_po', 'admin_lpb.no_sj')->where('admin_lpb.flag', 1)->get();

            return DataTables::of($lpbData)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-success btn-sm" onclick="showAddDetailModal(' .
                        $row->id .
                        ', \'' .
                        $row->id_lpb .
                        '\')">
                            Tambah Detail
                        </button>
                    ';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function checkStokAwal()
    {
        $exists = DB::table('admin_lpb')->where('flag', 1)->exists();
        return response()->json(['exists' => $exists]);
    }

    public function storeStokAwal(Request $request)
    {
        if (DB::table('admin_lpb')->where('flag', 1)->exists()) {
            return response()->json(['error' => 'Stok Awal sudah ada.'], 400);
        }

        DB::table('admin_lpb')->insert([
            'id_lpb' => 'STOKAWAL',
            'tanggal' => now(),
            'no_po' => 'STOKAWAL',
            'no_sj' => 'STOKAWAL',
            'flag' => 1,
            'id_user' => session('user_data')['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function getBahanDanKategori(Request $request)
    {
        $search = $request->input('q');

        $bahanQuery = DB::table('bahan')->select('id', 'nama as text');

        if (!empty($search)) {
            $bahanQuery->where('nama', 'LIKE', "%{$search}%");
        }

        $bahan = $bahanQuery->limit(10)->get();

        $kategori = null;
        $kategori_id = null;
        $satuan = null;
        if ($request->has('id_bahan')) {
            $idBahan = $request->input('id_bahan');
            $kategoriData = DB::table('bahan')->join('kategori_bahan', 'bahan.kategori', '=', 'kategori_bahan.katid')->where('bahan.id', $idBahan)->select('kategori_bahan.katnama as kategori', 'kategori_bahan.katid as kategori_id')->first();
            $satuanData = DB::table('bahan')->where('id', $idBahan)->select('satuan')->first();
            if ($kategoriData) {
                $kategori = $kategoriData->kategori;
                $kategori_id = $kategoriData->kategori_id;
            }

            if ($satuanData) {
                $satuan = $satuanData->satuan;
            }
        }

        return response()->json([
            'success' => true,
            'results' => $bahan,
            'kategori' => $kategori,
            'kategori_id' => $kategori_id,
            'satuan' => $satuan,
        ]);
    }

    public function storeLpbDetail(Request $request)
    {
        DB::beginTransaction();

        try {
            DB::table('admin_lpb_detail')->insert([
                'id_lpb' => $request->id_lpb,
                'id_bahan' => $request->id_bahan,
                'id_kategori' => $request->id_kategori,
                'jumlah_barang_diterima' => $request->jumlah_barang_diterima,
                'lot_number' => $request->lot_number ?? null,
                'harga' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('bahan')
                ->where('id', $request->id_bahan)
                ->update([
                    'stokawal' => DB::raw('stokawal + ' . $request->jumlah_barang_diterima),
                    'stok_onhand' => DB::raw('stok_onhand + ' . $request->jumlah_barang_diterima),
                    'updated_at' => now(),
                ]);
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    'error' => 'Gagal menyimpan data.',
                    'details' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                500,
            );
        }
    }

    public function getDetailStokAwal(Request $request)
    {
        $id_lpb = $request->input('id_lpb');
        $start = $request->input('start');
        $length = $request->input('length');
        $draw = $request->input('draw');
        $searchValue = $request->input('search')['value'];
        $query = DB::table('admin_lpb_detail')->join('bahan', 'admin_lpb_detail.id_bahan', '=', 'bahan.id')->join('kategori_bahan', 'admin_lpb_detail.id_kategori', '=', 'kategori_bahan.katid')->select('admin_lpb_detail.id as id_lpb_detail', 'bahan.nama as nama_barang', 'kategori_bahan.katnama as kategori', 'admin_lpb_detail.jumlah_barang_diterima', 'admin_lpb_detail.lot_number')->where('admin_lpb_detail.id_lpb', $id_lpb);
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('bahan.nama', 'LIKE', "%{$searchValue}%")
                    ->orWhere('kategori_bahan.katnama', 'LIKE', "%{$searchValue}%")
                    ->orWhere('admin_lpb_detail.jumlah_barang_diterima', 'LIKE', "%{$searchValue}%")
                    ->orWhere('admin_lpb_detail.lot_number', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();
        $details = $query->offset($start)->limit($length)->get();

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalFiltered,
            'recordsFiltered' => $totalFiltered,
            'data' => $details,
        ]);
    }

    public function updateDetailStokAwal(Request $request)
    {
        DB::beginTransaction();

        try {
            if (!$request->has('id_lpb_detail') || !$request->has('column') || !$request->has('value')) {
                return response()->json(['error' => 'Parameter yang dibutuhkan tidak lengkap.'], 400);
            }

            $oldDetail = DB::table('admin_lpb_detail')->where('id', $request->id_lpb_detail)->first();

            if (!$oldDetail) {
                return response()->json(['error' => 'Detail tidak ditemukan.'], 404);
            }

            if ($request->column === 'jumlah_barang_diterima') {
                if (!is_numeric($request->value)) {
                    return response()->json(['error' => 'Nilai yang diberikan tidak valid.'], 400);
                }

                $difference = $request->value - $oldDetail->jumlah_barang_diterima;

                if (!isset($oldDetail->id_bahan)) {
                    return response()->json(['error' => 'Bahan tidak ditemukan.'], 404);
                }

                DB::table('bahan')
                    ->where('id', $oldDetail->id_bahan)
                    ->update([
                        'stokawal' => DB::raw('stokawal + ' . $difference),
                        'stok_onhand' => DB::raw('stok_onhand + ' . $difference),
                        'updated_at' => now(),
                    ]);
            }

            DB::table('admin_lpb_detail')
                ->where('id', $request->id_lpb_detail)
                ->update([
                    $request->column => $request->value,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    'error' => 'Terjadi kesalahan saat memperbarui data.',
                    'details' => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function stokOpname()
    {
        $user = session('user_data');
        return view('gudang.stokopname', compact('user'));
    }

    public function adjustment()
    {
        $user = session('user_data');
        $items = DB::table('bahan')->select('id', 'nama', 'satuan')->orderBy('nama', 'asc')->get();

        return view('gudang.stokadjustment', [
            'user' => $user,
            'items' => $items,
        ]);
    }

    public function generateStockOpnameCode(Request $request)
    {
        $gudangId = $request->input('gudang_id', 0);

        $prefixes = [
            0 => 'STO-NK-Mentah-',
            1 => 'STO-NK-Persiapan-',
            2 => 'STO-NK-Produksi-',
            3 => 'STO-NK-Teknisi-'
        ];

        $prefix = $prefixes[$gudangId] ?? 'STO-NK-Gudang-';
        $currentYear = date('Y');
        $shortYear = substr($currentYear, -2);
        $fullPrefix = $prefix . $shortYear;

        $lastOpname = DB::table('stok_opname')
            ->where('kode', 'LIKE', $fullPrefix . '%')
            ->orderBy('kode', 'desc')
            ->first();

        if ($lastOpname) {
            $lastNumber = (int) substr($lastOpname->kode, -2);
            $newNumber = str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '01';
        }

        $newCode = $fullPrefix . $newNumber;
        return response()->json(['success' => true, 'kode' => $newCode]);
    }

    public function getStockOpnameData(Request $request)
    {
        $stockOpname = DB::table('stok_opname')
            ->select('id', 'kode', 'tanggal', 'flag', 'updated_at')
            ->orderBy('updated_at', 'DESC')
            ->get();

        return datatables()
            ->of($stockOpname)
            ->addIndexColumn()
            ->addColumn('action', function ($row) {
                $userData = session('user_data');
                $userType = $userData['type'] ?? null;
                $actionButtons = '<div class="btn-group">';

                if ($row->flag == 0 && $userType == 14) {
                    $actionButtons .= '<button class="btn btn-sm btn-info btn-edit mr-1" data-id="' . $row->id . '"><i class="fas fa-edit"></i></button>';
                    $actionButtons .= '<button class="btn btn-sm btn-danger btn-delete" data-id="' . $row->id . '"><i class="fas fa-trash"></i></button>';
                }

                $exportUrl = route('gudang.exportStockOpnameDetail', $row->id);
                $actionButtons .= '<a href="' . $exportUrl . '" class="btn btn-sm btn-success" title="Export ke Excel"><i class="fas fa-file-excel"></i> Export</a>';
                $actionButtons .= '</div>';

                return $actionButtons;
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function storeStockOpname(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|max:50|unique:stok_opname,kode',
            'tanggal' => 'required|date',
            'gudang_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $user = session('user_data');
        if (!$user || $user['type'] != 14) {
            return response()->json(['success' => false, 'message' => 'Hanya bagian Gudang yang diperbolehkan membuat sesi opname.'], 403);
        }

        $userId = $user['id'];
        DB::beginTransaction();

        try {
            $kode = $request->kode;
            while (DB::table('stok_opname')->where('kode', $kode)->exists()) {
                $kode = $this->incrementKode($kode);
            }

            $opnameId = DB::table('stok_opname')->insertGetId([
                'kode' => $kode,
                'tanggal' => $request->tanggal,
                'user_id' => $userId,
                'flag' => 0,
                'gudang_id' => $request->gudang_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $bahanData = DB::table('bahan')
                ->whereIn('jenis', [0, 1])
                ->where('kategori', '!=', 17)
                ->where('gudang', $request->gudang_id)
                ->get();

            if ($bahanData->isEmpty()) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Tidak ada data bahan di gudang yang dipilih.']);
            }

            $detailData = [];
            foreach ($bahanData as $bahan) {
                $lpbPrices = DB::table('admin_lpb_detail')
                    ->where('id_bahan', $bahan->id)
                    ->orderBy('id', 'desc')
                    ->limit(5)
                    ->pluck('harga');
                if ($lpbPrices->isNotEmpty()) {
                    $price = $lpbPrices->avg();
                } else {
                    $price = $bahan->harga;
                }

                $stokSistem = $bahan->stok_onhand;
                $stokReal = $bahan->stok_onhand;

                $detailData[] = [
                    'kode' => $kode,
                    'id_bahan' => $bahan->id,
                    'id_kategori' => $bahan->kategori,
                    'stok_sistem' => $stokSistem,
                    'stok_real' => $stokReal,
                    'harga' => $price,
                    'selisih' => $stokReal - $stokSistem,
                    'kerugian' => max(0, ($stokSistem - $stokReal) * $price),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('stok_opname_detail')->insert($detailData);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Stok opname berhasil ditambahkan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    protected function incrementKode($kode)
    {
        $prefix = substr($kode, 0, -2);
        $lastNumber = (int) substr($kode, -2);
        $newNumber = str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);

        return $prefix . $newNumber;
    }

    public function getDetailStockOpname(Request $request)
    {
        $kode = $request->input('kode');

        $query = DB::table('stok_opname_detail')
            ->join('bahan', 'stok_opname_detail.id_bahan', '=', 'bahan.id')
            ->join('kategori_bahan', 'bahan.kategori', '=', 'kategori_bahan.katid')
            ->select(
                'stok_opname_detail.id as id_detail',
                'bahan.nama as nama_bahan',
                'kategori_bahan.katnama as nama_kategori',
                'stok_opname_detail.stok_sistem',
                'stok_opname_detail.stok_real',
                'stok_opname_detail.harga',
                'stok_opname_detail.selisih',
                'stok_opname_detail.kerugian'
            )
            ->where('stok_opname_detail.kode', $kode);

        $totalData = $query->count();

        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('bahan.nama', 'LIKE', "%{$search}%")
                    ->orWhere('kategori_bahan.katnama', 'LIKE', "%{$search}%");
            });
        }

        $totalFiltered = $query->count();

        if ($request->has('order')) {
            $orderColumnIndex = $request->input('order.0.column');
            $orderDirection = $request->input('order.0.dir');
            $columnName = $request->input("columns.$orderColumnIndex.data");

            $columnsMap = [
                'id_detail'     => 'stok_opname_detail.id',
                'nama_bahan'    => 'bahan.nama',
                'nama_kategori' => 'kategori_bahan.katnama',
                'harga'         => 'stok_opname_detail.harga', // Tambahkan ini
                'stok_sistem'   => 'stok_opname_detail.stok_sistem',
                'stok_real'     => 'stok_opname_detail.stok_real',
                'selisih'       => 'stok_opname_detail.selisih',
                'kerugian'      => 'stok_opname_detail.kerugian', // Tambahkan ini
            ];

            if (array_key_exists($columnName, $columnsMap)) {
                $query->orderBy($columnsMap[$columnName], $orderDirection);
            } else {
                $query->orderBy('stok_opname_detail.id', 'asc');
            }
        } else {
            $query->orderBy('stok_opname_detail.id', 'asc');
        }

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        if ($length != -1) {
            $query->offset($start)->limit($length);
        }

        $data = $query->get();

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data'            => $data,
        ]);
    }

    public function updateDetailStockOpname(Request $request)
    {
        try {
            $request->validate([
                'id_detail' => 'required|exists:stok_opname_detail,id',
                'column' => 'required|in:stok_real,harga',
                'value' => 'required|numeric|min:0',
            ]);

            $detail = DB::table('stok_opname_detail')->where('id', $request->id_detail)->first();

            if (!$detail) {
                return response()->json(['success' => false, 'message' => 'ID tidak ditemukan.'], 404);
            }

            $updateData = [
                $request->column => $request->value,
                'updated_at' => now(),
            ];

            $stok_sistem = $detail->stok_sistem;
            $stok_real_baru = $request->column == 'stok_real' ? $request->value : $detail->stok_real;
            $harga_baru = $request->column == 'harga' ? $request->value : $detail->harga;

            $selisih = $stok_real_baru - $stok_sistem;
            $kerugian = max(0, ($stok_sistem - $stok_real_baru) * $harga_baru);

            $updateData['selisih'] = $selisih;
            $updateData['kerugian'] = $kerugian;

            DB::table('stok_opname_detail')->where('id', $request->id_detail)->update($updateData);

            return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui.']);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal memperbarui data: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function getStockOpnameById($id)
    {
        try {
            $opname = DB::table('stok_opname')->where('id', $id)->first();

            if (!$opname) {
                return response()->json(['success' => false, 'message' => 'Data stok opname tidak ditemukan.'], 404);
            }

            return response()->json(['success' => true, 'data' => $opname]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function updateStockOpname(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        try {
            DB::table('stok_opname')
                ->where('id', $id)
                ->update([
                    'tanggal' => $request->tanggal,
                    'updated_at' => now(),
                ]);

            return response()->json(['success' => true, 'message' => 'Tanggal stok opname berhasil diperbarui']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function deleteStockOpname(Request $request, $id)
    {
        try {
            $opname = DB::table('stok_opname')->where('id', $id)->first();

            if (!$opname) {
                return response()->json(['success' => false, 'message' => 'Data stok opname tidak ditemukan.']);
            }
            DB::beginTransaction();
            DB::table('stok_opname_detail')->where('kode', $opname->kode)->delete();
            DB::table('stok_opname')->where('id', $id)->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Stok opname berhasil dihapus']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function completeStockOpname($id)
    {
        try {
            DB::table('stok_opname')
                ->where('id', $id)
                ->update(['flag' => 1, 'updated_at' => now()]);
            return response()->json(['success' => true, 'message' => 'Stok opname berhasil ditandai sebagai selesai.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function finalizeStockOpname(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $opname = DB::table('stok_opname')->where('id', $id)->first();

            if (!$opname) {
                return response()->json(['success' => false, 'message' => 'Data Stok Opname tidak ditemukan.'], 404);
            }
            if ($opname->flag == 2) {
                return response()->json(['success' => false, 'message' => 'Stok Opname ini sudah pernah disetujui.'], 400);
            }
            if ($opname->flag == 0) {
                return response()->json(['success' => false, 'message' => 'Stok Opname ini belum selesai dikerjakan.'], 400);
            }
            $opnameDetails = DB::table('stok_opname_detail')->join('bahan', 'stok_opname_detail.id_bahan', '=', 'bahan.id')->where('stok_opname_detail.kode', $opname->kode)->select('stok_opname_detail.*', 'bahan.nama as nama_bahan', 'bahan.satuan')->get();
            $user = $request->user();
            $operatorName = $user ? $user->name : 'API System';
            foreach ($opnameDetails as $detail) {
                DB::table('bahan')
                    ->where('id', $detail->id_bahan)
                    ->update(['stok_onhand' => DB::raw('stok_onhand + ' . $detail->selisih)]);

                if ($detail->selisih != 0) {
                    DB::table('stock_adjustments')->insert([
                        'kode' => 'OPNAME ' . $opname->kode,
                        'tanggal' => now(),
                        'keterangan' => 'Hasil Opname ' . $opname->kode,
                        'operator' => $operatorName,
                        'id_barang' => $detail->id_bahan,
                        'nama_barang' => $detail->nama_bahan,
                        'jumlah' => $detail->selisih,
                        'satuan' => $detail->satuan,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::table('stok_opname')
                ->where('id', $id)
                ->update(['flag' => 2, 'updated_at' => now()]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Stok berhasil disetujui dan stok on-hand telah disesuaikan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API Gagal menyetujui opname: ' . $e->getMessage(), ['opname_id' => $id]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat memproses persetujuan.'], 500);
        }
    }

    public function ambilPengajuanOpname(Request $request)
    {
        try {
            $pengajuan = DB::table('stok_opname')
                ->whereIn('flag', [1, 2])
                ->select('id', 'kode', 'tanggal', 'flag')
                ->orderBy('tanggal', 'desc')
                ->get();

            if ($pengajuan->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tidak ada data opname yang menunggu persetujuan.',
                    'data' => [],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data pengajuan opname berhasil diambil.',
                'data' => $pengajuan,
            ]);
        } catch (\Exception $e) {
            Log::error('API Gagal mengambil data opname: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.',
            ], 500);
        }
    }
    public function getDetailOpnameNonKertas(Request $request)
    {
        if (!$request->has('kode')) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Parameter "kode" diperlukan.',
                ],
                400,
            );
        }

        try {
            $kodeOpname = $request->input('kode');
            $details = DB::table('stok_opname_detail as sod')->join('bahan as mb', 'sod.id_bahan', '=', 'mb.id')->where('sod.kode', $kodeOpname)->where('sod.selisih', '!=', 0)->select('mb.nama as nama_bahan', 'mb.harga', 'sod.stok_sistem', 'sod.stok_real', 'sod.selisih', DB::raw('(sod.selisih * mb.harga) as total_nilai'))->get();
            return response()->json([
                'success' => true,
                'message' => 'Detail opname berhasil diambil.',
                'data' => $details,
            ]);
        } catch (\Exception $e) {
            Log::error('API Gagal mengambil detail opname: ' . $e->getMessage());
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Terjadi kesalahan pada server saat mengambil detail.',
                ],
                500,
            );
        }
    }

    public function exportStockOpnameDetail($id)
    {
        $userData = session('user_data');
        $userType = $userData['type'] ?? null;

        $opname = DB::table('stok_opname')->where('id', $id)->first();

        if (!$opname) {
            return redirect()->back()->with('error', 'Data Stok Opname tidak ditemukan.');
        }

        $fileName = 'Stok-Opname-Detail-' . $opname->kode . '.xlsx';

        return Excel::download(new StockOpnameDetailExport($opname->kode, $userType), $fileName);
    }

    public function checkSuratJalan(Request $request)
    {
        $nomorSuratJalan = $request->nomor_surat_jalan;
        $existingSj = DB::table('admin_lpb')->where('no_sj', $nomorSuratJalan)->first();
        if ($existingSj) {
            return response()->json(['success' => false, 'message' => 'Nomor Surat Jalan sudah dipakai!']);
        }

        return response()->json(['success' => true, 'message' => 'Nomor Surat Jalan belum dipakai.']);
    }
    public function historibahan(Request $request)
    {
        $namaBhan = $request->input('nama');
        if (!$namaBhan) {
            return response()->json(['success' => false, 'message' => "Parameter 'nama' diperlukan."], 400);
        }
        $bahan = DB::table('bahan')->where('nama', $namaBhan)->first();
        if (!$bahan) {
            return response()->json(['success' => false, 'message' => 'Bahan dengan nama "' . $namaBhan . '" tidak ditemukan.'], 404);
        }
        $riwayatMasuk = DB::table('admin_lpb_detail')
            ->join('admin_lpb', 'admin_lpb_detail.id_lpb', '=', 'admin_lpb.id_lpb')
            ->where('admin_lpb_detail.id_bahan', $bahan->id)
            ->select('admin_lpb.tanggal', 'admin_lpb.no_po as no_referensi', 'admin_lpb_detail.jumlah_barang_diterima as jumlah', 'admin_lpb.no_sj as keterangan')
            ->get()
            ->map(function ($item) {
                $item->tipe = 'Masuk';
                $item->jenis_transaksi = 'Penerimaan';
                return $item;
            });

        $riwayatKeluar = DB::table('npk_planning')
            ->where('id_barang', $bahan->id)
            ->where('jumlah_terkirim', '>', 0)
            ->select('tgl_terkirim as tanggal', 'kode as no_referensi', 'jumlah_terkirim as jumlah', 'keterangan')
            ->get()
            ->map(function ($item) {
                $item->tipe = 'Keluar';
                $item->jenis_transaksi = 'Pemakaian';
                $item->jumlah = -abs($item->jumlah);
                return $item;
            });

        $riwayatAdjustment = DB::table('stock_adjustments')
            ->where('id_barang', $bahan->id)
            ->select('tanggal', 'kode as no_referensi', 'jumlah', 'keterangan')
            ->get()
            ->map(function ($item) {
                if ($item->jumlah >= 0) {
                    $item->tipe = 'Masuk';
                } else {
                    $item->tipe = 'Keluar';
                }
                $item->jenis_transaksi = 'Penyesuaian';
                return $item;
            });

        $riwayatGabungan = $riwayatMasuk->merge($riwayatKeluar)->merge($riwayatAdjustment);
        $riwayatUrut = $riwayatGabungan->sortBy('tanggal')->values()->all();

        return response()->json([
            'success' => true,
            'bahan' => $bahan,
            'riwayat' => $riwayatUrut,
        ]);
    }

    public function stokadjust(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'keterangan' => 'required|string|max:255|unique:stock_adjustments_temporary,keterangan,NULL,id,status,pengajuan',
            'operator' => 'required|string|max:100',
            'details' => 'required|array|min:1',
            'details.*.id_barang' => 'required|exists:bahan,id',
            'details.*.nama_barang' => 'required|string',
            'details.*.jumlah' => 'required|numeric|not_in:0',
            'details.*.satuan' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $details = $request->input('details');
            $header = $request->only(['tanggal', 'keterangan', 'operator']);

            $kode_adj = 'ADJ' . Carbon::parse($header['tanggal'])->format('md');

            foreach ($details as $item) {
                DB::table('stock_adjustments_temporary')->insert([
                    'kode' => $kode_adj,
                    'tanggal' => $header['tanggal'],
                    'keterangan' => $header['keterangan'],
                    'operator' => $header['operator'],
                    'id_barang' => $item['id_barang'],
                    'nama_barang' => $item['nama_barang'],
                    'jumlah' => $item['jumlah'],
                    'satuan' => $item['satuan'],
                    'status' => 'pengajuan',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'Pengajuan stok adjustment berhasil disimpan!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function ambilstokadjust(Request $request)
    {
        try {
            $data = DB::table('stock_adjustments_temporary')->where('status', 'pengajuan')->select('kode', DB::raw('MAX(tanggal) as tanggal'), DB::raw("GROUP_CONCAT(DISTINCT keterangan SEPARATOR ', ') as list_keterangan"), DB::raw("GROUP_CONCAT(DISTINCT operator SEPARATOR ', ') as list_operator"))->groupBy('kode')->orderBy('tanggal', 'desc')->get();

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function setujuiStokAdjust(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|exists:stock_adjustments_temporary,kode,status,pengajuan',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Kode pengajuan tidak valid atau sudah diproses.', 'errors' => $validator->errors()], 422);
        }

        $kode = $request->input('kode');

        DB::beginTransaction();
        try {
            $itemsToProcess = DB::table('stock_adjustments_temporary')->where('kode', $kode)->where('status', 'pengajuan')->get();

            if ($itemsToProcess->isEmpty()) {
                return response()->json(['message' => 'Tidak ada pengajuan yang ditemukan dengan kode ini.'], 404);
            }

            foreach ($itemsToProcess as $item) {
                DB::table('stock_adjustments')->insert([
                    'kode' => $item->kode,
                    'tanggal' => $item->tanggal,
                    'keterangan' => $item->keterangan,
                    'operator' => $item->operator,
                    'id_barang' => $item->id_barang,
                    'nama_barang' => $item->nama_barang,
                    'jumlah' => $item->jumlah,
                    'satuan' => $item->satuan,
                    'created_at' => $item->created_at,
                    'updated_at' => now(),
                ]);

                $jumlah = floatval($item->jumlah);
                $affectedRows = 0;

                if ($jumlah > 0) {
                    $affectedRows = DB::table('bahan')->where('id', $item->id_barang)->increment('stok_onhand', $jumlah);
                } else {
                    $affectedRows = DB::table('bahan')->where('id', $item->id_barang)->decrement('stok_onhand', abs($jumlah));
                }
                if ($affectedRows === 0) {
                    DB::rollBack();
                    $errorMessage = 'Gagal! Barang "' . $item->nama_barang . '" (ID: ' . $item->id_barang . ') tidak ditemukan di master data bahan. Seluruh proses dibatalkan.';
                    Log::error($errorMessage);
                    return response()->json(['message' => $errorMessage], 404);
                }
            }

            DB::table('stock_adjustments_temporary')
                ->where('kode', $kode)
                ->where('status', 'pengajuan')
                ->update(['status' => 'sudah masuk data', 'updated_at' => now()]);

            DB::commit();

            return response()->json(['message' => 'Stok adjustment berhasil disetujui dan stok telah diperbarui!']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyetujui adjustment via API: ' . $e->getMessage(), ['kode' => $kode]);
            return response()->json(['message' => 'Terjadi kesalahan pada server saat memproses persetujuan.'], 500);
        }
    }

    public function getAdjustmentHistory(Request $request)
    {
        $data = DB::table('stock_adjustments_temporary')->select('kode', DB::raw('MAX(tanggal) as tanggal'), DB::raw('MAX(operator) as operator'), 'status')->groupBy('kode', 'status')->orderBy('tanggal', 'desc');

        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('status', function ($row) {
                return $row->status == 'pengajuan' ? '<span class="badge badge-warning">Diajukan</span>' : '<span class="badge badge-success">Sudah Masuk Data</span>';
            })
            ->addColumn('tindakan', function ($row) {
                if ($row->status == 'pengajuan') {
                    return '<button class="btn btn-sm btn-success btn-approve" data-kode="' . $row->kode . '">Setujui</button>';
                }
                return '<button class="btn btn-sm btn-secondary" disabled>Disetujui</button>';
            })
            ->rawColumns(['tindakan', 'status'])
            ->make(true);
    }

    public function getAdjustmentDetails(Request $request)
    {
        $request->validate(['kode' => 'required|string']);
        $kode = $request->input('kode');
        $historyQuery = DB::table('stock_adjustments')->where('kode', $kode)->select('nama_barang', 'jumlah', 'satuan', 'keterangan');
        $details = DB::table('stock_adjustments_temporary')->where('kode', $kode)->select('nama_barang', 'jumlah', 'satuan', 'keterangan')->union($historyQuery)->get();
        if ($details->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Detail tidak ditemukan.'], 404);
        }
        return response()->json(['success' => true, 'data' => $details]);
    }

    public function searchadjustment(Request $request)
    {
        $searchTerm = $request->input('q');
        $query = DB::table('bahan')->select('id', 'nama as text', 'satuan');

        if ($searchTerm) {
            $query->where('nama', 'LIKE', '%' . $searchTerm . '%');
        }
        $items = $query->orderBy('nama', 'asc')->limit(50)->get();
        return response()->json(['results' => $items]);
    }

    public function bukalpbdata($id_data)
    {
        $lpb = DB::table('admin_lpb')->where('id_lpb', $id_data)->first();

        if (!$lpb) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal! Data LPB dengan ID ' . $id_data . ' tidak ditemukan.',
                ],
                404,
            );
        }

        try {
            $newKunci = $lpb->kunci == 0 ? 1 : 0;
            $statusText = $newKunci == 1 ? 'dikunci' : 'dibuka';

            DB::table('admin_lpb')
                ->where('id_lpb', $id_data)
                ->update(['kunci' => $newKunci]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil! Kunci untuk LPB ' . $id_data . ' telah ' . $statusText . '.',
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Terjadi kesalahan pada server saat mencoba mengubah status kunci.',
                ],
                500,
            );
        }
    }

    public function getLpbDataapi(Request $request)
    {
        $lpbQuery = DB::table('admin_lpb')
            ->select('admin_lpb.id_lpb', 'admin_lpb.tanggal', 'admin_lpb.no_po', 'admin_lpb.no_sj', 'admin_lpb.kunci', 'suppliers.nama as supplier_nama', 'suppliers.alamat as supplier_alamat')
            ->leftJoin('inv_po', 'admin_lpb.no_po', '=', 'inv_po.no_po')
            ->leftJoin('suppliers', 'inv_po.id_suplier', '=', 'suppliers.id')
            ->where('admin_lpb.flag', 0)
            ->when($request->filterMonthYear, function ($query, $monthYear) {
                [$year, $month] = explode('-', $monthYear);
                return $query->whereYear('admin_lpb.tanggal', $year)->whereMonth('admin_lpb.tanggal', $month);
            })
            ->when($request->filled('filterLpbType') && $request->filterLpbType != 'all', function ($query) use ($request) {
                return $query->where('admin_lpb.id_lpb', 'like', $request->filterLpbType . '%');
            })
            ->when($request->searchTerm, function ($query, $searchTerm) {
                return $query->where(function ($q) use ($searchTerm) {
                    $q->where('admin_lpb.id_lpb', 'like', "%{$searchTerm}%")
                        ->orWhere('admin_lpb.no_po', 'like', "%{$searchTerm}%")
                        ->orWhere('admin_lpb.no_sj', 'like', "%{$searchTerm}%")
                        ->orWhere('suppliers.nama', 'like', "%{$searchTerm}%");
                });
            });

        $totalRecords = DB::table('admin_lpb')->where('flag', 0)->count();
        $filteredRecordsCount = $lpbQuery->count();

        $lpbData = $lpbQuery
            ->orderByDesc('admin_lpb.tanggal')
            ->skip($request->start ?? 0)
            ->take($request->length ?? 10)
            ->get();

        $data = $lpbData->map(function ($item, $index) use ($request) {
            if ($item->kunci == 1) {
                $actionsButton = '<button class="btn btn-secondary btn-sm" disabled title="LPB sudah dikunci"><i class="fas fa-lock"></i> Tidak Bisa Cetak</button>';
            } else {
                $actionsButton = '<button class="btn btn-primary btn-sm btn-cetak" data-id="' . $item->id_lpb . '" title="Cetak LPB"><i class="far fa-file-pdf"></i> Cetak</button>';
            }

            return [
                'no' => $index + 1 + ($request->start ?? 0),
                'id_lpb' => $item->id_lpb,
                'tanggal' => $item->tanggal,
                'no_po' => $item->no_po,
                'no_sj' => $item->no_sj,
                'supplier_nama' => $item->supplier_nama,
                'supplier_alamat' => $item->supplier_alamat,
                'actions' => $actionsButton,
                'kunci' => $item->kunci,
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecordsCount,
            'data' => $data,
        ]);
    }
}

class MyPDF extends TCPDF
{
    private $footer_text;

    public function setFooterText($text)
    {
        $this->footer_text = $text;
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, $this->footer_text, 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}
