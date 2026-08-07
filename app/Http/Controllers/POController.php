<?php

namespace App\Http\Controllers;

use App\Models\DetailPOModel;
use App\Models\PoHistory;
use App\Models\POModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TCPDF;

class POController extends Controller
{
    public function index()
    {
        $user = session('user_data');
        $title = 'PO List Transaction';
        $jenis = 0;
        return view('purchasing.po.index', compact('user', 'jenis', 'title'));
    }
    public function createpp()
    {
        $user = session('user_data');
        $title = 'PP List Transaction';
        $jenis = 1;
        return view('purchasing.po.index', compact('user', 'jenis', 'title'));
    }
    //createnon
    public function createnon()
    {
        $user = session('user_data');
        $title = 'NON PO/PP List Transaction';
        $jenis = 2;
        return view('purchasing.po.index', compact('user', 'jenis', 'title'));
    }
    public function show($id)
    {
        // Mencari data detail PO berdasarkan ID dengan join ke tabel bahan
        $detailPO = DetailPOModel::where('inv_podetail.id', $id)
            ->join('bahan', 'inv_podetail.id_bahan', '=', 'bahan.id')
            ->select(
                'inv_podetail.*',
                'bahan.nama',
                'bahan.satuan'
            )
            ->first();

        // Jika data tidak ditemukan, kembalikan respons error
        if (!$detailPO) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Mengembalikan data detail PO dalam bentuk JSON
        return response()->json($detailPO);
    }
    // public function update(Request $request, $id)
    // {
    //     $detail = DetailPOModel::find($id);

    //     // Jika data tidak ditemukan
    //     if (!$detail) {
    //         return response()->json(['message' => 'Data tidak ditemukan'], 404);
    //     }

    //     $harga = $request->input('harga');
    //     $jumlah = $request->input('jumlah');

    //     $nilaippn = $request->input('nilaippn');
    //     $exclude = $harga * $jumlah;
    //     if ($nilaippn != 0) {
    //         $ppn = $exclude * $nilaippn / 100;
    //     } else {
    //         $ppn = 0;
    //     }
    //     $detail->jumlah = $jumlah;
    //     $detail->harga = $harga;
    //     $detail->exclude = $exclude;
    //     $detail->ppn = $ppn;
    //     $detail->include = $exclude + $ppn;
    //     $detail->save();
    //     return response()->json([
    //         'message' => 'OK',
    //         'no_po' => $detail->no_po
    //     ]);
    // }
    public function update(Request $request, $id)
    {
        DB::beginTransaction(); // Mulai transaksi database
        try {

            $detail = DetailPOModel::find($id);

            // Jika data tidak ditemukan
            if (!$detail) {
                return response()->json(['message' => 'Data tidak ditemukan'], 404);
            }

            $pokertas = POModel::find($detail->no_po);
            if ($pokertas && $pokertas->cetak >= 1) {
                DB::statement('CALL SP_ArchivePo(?)', [$pokertas->no_po]);
                $pokertas->cetak_ulang = '1';
            }

            // Inputan baru
            $harga = $request->input('harga');

            $jumlah = $request->input('jumlah');


            $nilaippn =  $pokertas->ppn;

            $exclude = round($harga * $jumlah, 2);
            $oldexclude = round($detail->exclude, 2);

            $oldtotalexclude = round($pokertas->totalexclude, 2);
            if ($nilaippn != 0) {
                $ppn = round($exclude * $nilaippn / 100, 2);
            } else {
                $ppn = 0;
            }

            // Hitung ulang total
            $newtotalexclude = $oldtotalexclude + ($exclude - $oldexclude);
            if ($pokertas->ppn == 0) {
                $newtotalinclude = $newtotalexclude;
                $newtotalppn = 0;
            } else {
                $newtotalppn = round(($newtotalexclude - $pokertas->diskon) * $pokertas->ppn / 100, 2);
                $newtotalinclude = $newtotalexclude + $newtotalppn;
            }

            // Update inv_po

            $pokertas->totalppn = $newtotalppn;
            $pokertas->totalexclude = $newtotalexclude;
            $pokertas->totalinclude = $newtotalinclude;
            $pokertas->GrandTotalPembelian = $newtotalexclude + $newtotalppn - $pokertas->diskon + $pokertas->ongkir;
            $pokertas->save();

            $detail->jumlah = $jumlah;
            $detail->harga = $harga;
            $detail->exclude = $exclude;
            $detail->ppn = $ppn;
            $detail->include = $exclude + $ppn;
            $detail->save();

            DB::commit(); // Simpan perubahan jika tidak ada error

            return response()->json([
                'message' => 'OK',
                'no_po' => $detail->no_po
            ]);
        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan semua perubahan jika terjadi error
            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function lihatcetak(Request $request)
    {
        $nomorpo = $request->input('nomorpo');

        // Contoh pengambilan data berdasarkan nomorpo dari model
        $header = POModel::where('no_po', $nomorpo)
            ->join('suppliers', 'inv_po.id_suplier', '=', 'suppliers.id')
            ->select(
                'inv_po.*',
                'suppliers.*'
            )
            ->first();
        $detailsx = DetailPOModel::detail($nomorpo);

        if (!$header) {
            return response()->json(['message' => 'Data PO tidak ditemukan.'], 404);
        }

        // --- PERUBAHAN DI SINI ---
        // Mengambil data riwayat dari model PoHistory yang sudah kita buat
        $historyRevisions = PoHistory::where('no_po', $nomorpo)
            ->orderBy('archived_at', 'desc')
            ->get();

        // Kirim variabel $historyRevisions ke dalam view
        return view('purchasing.po.preview', compact('header', 'detailsx', 'historyRevisions'));
    }
    // di dalam controller Anda (misal: POController.php)



    public function cetakRevisi(Request $request)
    {
        // Validasi input untuk keamanan
        $request->validate(['no_revisi' => 'required|string|exists:inv_po_history,no_revisi']);
        $noRevisi = $request->input('no_revisi');

        // Ambil data dari tabel histori beserta relasi detailnya
        $historyData = PoHistory::with(['details.bahan', 'supplier'])->where('no_revisi', $noRevisi)->firstOrFail();
        // Siapkan variabel-variabel yang dibutuhkan oleh view
        $dataForView = [
            'header' => $historyData, // Data utama sekarang dari histori
            'detailsx' => $historyData->details, // Data detail dari histori
            'pdfTitle' => 'ARSIP PO ' . $historyData->no_po,
            'no_order' => $historyData->no_order != '-' ? $historyData->no_order : '',
            'up' => $historyData->untukperhatian,
            'badan_usaha' => $historyData->badan_usaha == "1" ? "CV . " : ($historyData->badan_usaha == "2" ? "UD. " : ($historyData->badan_usaha == "3" ? "PERORANGAN " : ($historyData->badan_usaha == "4" ? " " : "PT. "))),
            'tanggalFormatted' => date('d F Y', strtotime($historyData->tanggal)),
            'data' => ['title' => 'PT.MULIAOFFSET PACKINDO'],
            'footer_text' => "Salinan Revisi ($historyData->no_revisi) - Diarsip pada " . date('d-m-Y H:i', strtotime($historyData->archived_at)),
        ];

        // Render view BARU kita menjadi HTML
        $html = view('purchasing.previewhistory', $dataForView)->render();

        // Buat dan tampilkan PDF
        $pageLayout = array(216, 214);
        $pdf = new TCPDF('P', 'mm', $pageLayout, true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(4, 4, 5);
        $pdf->SetTitle($dataForView['pdfTitle']);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        ob_end_clean();
        return $pdf->Output('REVISI-' . $historyData->no_po . '.pdf', 'I');
    }
}
