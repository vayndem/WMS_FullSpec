<?php

namespace App\Http\Controllers;

use App\Models\Jasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use TCPDF;

class JasaController extends Controller
{
    public function index()
    {
        $user = session('user_data');
        $title = 'Jasa List Transaction';
        return view('jasa.index', compact('user', 'title'));
    }

    public function getDataJasa(Request $request)
    {
        $query = Jasa::query();

        if ($request->bulan != 0) {
            $query->whereMonth('tanggal', $request->bulan);
        }
        if ($request->tahun) {
            $query->whereYear('tanggal', $request->tahun);
        }

        return DataTables::of($query)->make(true);
    }

    public function create()
    {
        $title = 'Tambah Transaksi Jasa Baru';
        $data = view('jasa.formtambah', compact('title'))->render();
        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $excl = $request->input('totalexclude', 0);
            $diskon = $request->input('diskon', 0);
            $ppnRate = $request->input('ppn', 0);
            $ongkir = $request->input('ongkir', 0);

            $basisPpn = $excl - $diskon;
            $totalPpn = $ppnRate > 0 ? round(($basisPpn * $ppnRate) / 100, 2) : 0;
            $totalInclude = $excl + $totalPpn;
            $grandTotal = $excl - $diskon + $totalPpn + $ongkir;

            $jasa = new Jasa();
            $jasa->no_jasa = $request->input('no_jasa') ?? 'JS-' . date('YmdHis');
            $jasa->nama = $request->input('nama');
            $jasa->no_order = $request->input('no_order', '-');
            $jasa->untukperhatian = $request->input('untukperhatian', '-');
            $jasa->tanggal = $request->input('tanggal');
            $jasa->term = $request->input('term');
            $jasa->term_pengiriman = $request->input('term_pengiriman', 'Tidak Ada');
            $jasa->totalexclude = $excl;
            $jasa->diskon = $diskon;
            $jasa->ppn = $ppnRate;
            $jasa->totalppn = $totalPpn;
            $jasa->totalinclude = $totalInclude;
            $jasa->inputlabel = $request->input('inputlabel', 'Freight Handling');
            $jasa->ongkir = $ongkir;
            $jasa->GrandTotalPembelian = $grandTotal;
            $jasa->status = $request->input('status', 0);
            $jasa->user_id = session('user_data')['id'] ?? 1;
            $jasa->save();

            DB::commit();
            return response()->json(['message' => 'OK']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        $jasa = Jasa::findOrFail($id);
        $title = 'Edit Transaksi Jasa: ' . $jasa->no_jasa;
        $data = view('jasa.formtambah', compact('title', 'jasa'))->render();
        return response()->json(['data' => $data]);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $jasa = Jasa::find($id);

            if (!$jasa) {
                return response()->json(['message' => 'Data tidak ditemukan'], 404);
            }

            if ($request->has('closestatus')) {
                $jasa->status = $request->input('closestatus');
                $jasa->save();
                DB::commit();
                return response()->json(['message' => 'OK']);
            }

            if ($jasa->cetak >= 1) {
                DB::statement('CALL SP_ArchiveJasa(?)', [$jasa->no_jasa]);
                $jasa->cetak_ulang = '1';
            }

            $excl = $request->input('totalexclude', 0);
            $diskon = $request->input('diskon', 0);
            $ppnRate = $request->input('ppn', 0);
            $ongkir = $request->input('ongkir', 0);

            $basisPpn = $excl - $diskon;
            $totalPpn = $ppnRate > 0 ? round(($basisPpn * $ppnRate) / 100, 2) : 0;
            $totalInclude = $excl + $totalPpn;
            $grandTotal = $excl - $diskon + $totalPpn + $ongkir;

            $jasa->nama = $request->input('nama');
            $jasa->no_order = $request->input('no_order', '-');
            $jasa->untukperhatian = $request->input('untukperhatian', '-');
            $jasa->tanggal = $request->input('tanggal');
            $jasa->term = $request->input('term');
            $jasa->term_pengiriman = $request->input('term_pengiriman', 'Tidak Ada');
            $jasa->totalexclude = $excl;
            $jasa->diskon = $diskon;
            $jasa->ppn = $ppnRate;
            $jasa->totalppn = $totalPpn;
            $jasa->totalinclude = $totalInclude;
            $jasa->inputlabel = $request->input('inputlabel', 'Freight Handling');
            $jasa->ongkir = $ongkir;
            $jasa->GrandTotalPembelian = $grandTotal;
            $jasa->status = $request->input('status', 0);
            $jasa->save();

            DB::commit();
            return response()->json(['message' => 'OK']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function lihatcetak(Request $request)
    {
        $nomorjasa = $request->input('nomorjasa');
        $header = Jasa::where('no_jasa', $nomorjasa)->first();

        if (!$header) {
            return response()->json(['message' => 'Data Jasa tidak ditemukan.'], 404);
        }

        return view('jasa.preview', compact('header'));
    }

    public function cetakDirect(Request $request)
    {
        $nomorjasa = $request->input('nomorjasa');
        $jasaData = Jasa::where('no_jasa', $nomorjasa)->firstOrFail();

        $dataForView = [
            'header' => $jasaData,
            'pdfTitle' => 'JASA ' . $jasaData->no_jasa,
            'no_order' => $jasaData->no_order != '-' ? $jasaData->no_order : '',
            'up' => $jasaData->untukperhatian,
            'tanggalFormatted' => date('d F Y', strtotime($jasaData->tanggal)),
            'data' => ['title' => 'PT.MULIAOFFSET PACKINDO'],
            'footer_text' => "Dokumen Jasa Utama (" . $jasaData->no_jasa . ") - Dicetak pada " . date('d-m-Y H:i'),
        ];

        $html = view('jasa.preview', $dataForView)->render();

        $pageLayout = array(216, 214);
        $pdf = new TCPDF('P', 'mm', $pageLayout, true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(4, 4, 5);
        $pdf->SetTitle($dataForView['pdfTitle']);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        ob_end_clean();
        return $pdf->Output('JASA-' . $jasaData->no_jasa . '.pdf', 'I');
    }
}
