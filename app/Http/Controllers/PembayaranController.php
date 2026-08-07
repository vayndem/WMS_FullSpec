<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PembayaranSuplierExport;

class PembayaranController extends Controller
{
    public function index()
    {
        $user = session('user_data');

        return view('finance.index', compact('user'));
    }

    public function data(Request $request)
    {
        $query = DB::table('invoice_lpb')
            ->leftJoin('suppliers', 'invoice_lpb.kode_supplier', '=', 'suppliers.id')
            ->leftJoin('inv_jasa', 'invoice_lpb.no_invoice', '=', 'inv_jasa.no_jasa');

        if ($request->input('status_filter') == 'lunas') {
            $query->where('invoice_lpb.status', 2);
        } else {
            $query->where('invoice_lpb.status', '<', 2);
        }

        $columns = [
            'invoice_lpb.id',
            'invoice_lpb.no_invoice',
            'invoice_lpb.grand_total',
            'invoice_lpb.sisa_tagihan',
            'invoice_lpb.tgl_deadline_pembayaran',
            'invoice_lpb.status_pembayaran',
            'invoice_lpb.tanggal as tanggal_nota',
            'invoice_lpb.status',
            DB::raw('COALESCE(suppliers.nama, inv_jasa.nama) as nama_supplier'),
            DB::raw('COALESCE((SELECT GROUP_CONCAT(id_lpb SEPARATOR ", ") FROM admin_lpb WHERE admin_lpb.no_invoice = invoice_lpb.no_invoice), inv_jasa.no_jasa) as list_lpb'),
            DB::raw('COALESCE((SELECT ip.jenis FROM inv_po ip JOIN admin_lpb al ON ip.no_po = al.no_po WHERE al.no_invoice = invoice_lpb.no_invoice LIMIT 1), 3) as jenis_transaksi'),
            DB::raw('(SELECT MAX(tanggal_pembayaran) FROM pembayaran_transaksi_detail WHERE id_invoice_lpb = invoice_lpb.id) as tanggal_pembayaran_terakhir'),
            DB::raw('(SELECT MIN(tanggal_pembayaran) FROM pembayaran_transaksi_detail WHERE id_invoice_lpb = invoice_lpb.id) as tanggal_bayar_awal')
        ];

        $query->select($columns);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('details_control', fn($row) => '')
            ->addColumn('list_lpb', fn($row) => $row->list_lpb)
            ->addColumn('kode_supplier', fn($row) => $row->nama_supplier)
            ->addColumn('tanggal_bayar', function ($row) {
                return $row->tanggal_bayar_awal ? Carbon::parse($row->tanggal_bayar_awal)->format('d M Y') : '-';
            })
            ->editColumn('grand_total', fn($row) => 'Rp ' . number_format($row->grand_total, 2, ',', '.'))
            ->editColumn('sisa_tagihan', fn($row) => 'Rp ' . number_format($row->sisa_tagihan, 2, ',', '.'))
            ->editColumn('tgl_deadline_pembayaran', function ($row) {
                $deadline = Carbon::parse($row->tgl_deadline_pembayaran);
                $isOverdue = $deadline->isPast() && $row->sisa_tagihan > 0;
                return '<span class="' . ($isOverdue ? 'text-danger font-weight-bold' : '') . '">' . $deadline->format('d M Y') . '</span>';
            })
            ->editColumn('status_pembayaran', function ($row) {
                if ($row->status_pembayaran == 'Lunas') {
                    return '<span class="badge badge-success">Lunas</span>';
                }
                if ($row->status_pembayaran == 'Dibayar Sebagian') {
                    return '<span class="badge badge-warning">Dibayar Sebagian</span>';
                }
                return '<span class="badge badge-danger">Belum Dibayar</span>';
            })
            ->addColumn('jenis_transaksi_label', function ($row) {
                if (!isset($row->jenis_transaksi)) {
                    return '<span class="badge badge-secondary">N/A</span>';
                }
                switch ((int) $row->jenis_transaksi) {
                    case 0:
                        return '<span class="badge badge-primary">PO</span>';
                    case 1:
                        return '<span class="badge badge-info">PP</span>';
                    case 3:
                        return '<span class="badge badge-success">JASA</span>';
                    default:
                        return '<span class="badge badge-secondary">Lainnya</span>';
                }
            })
            ->addColumn('action', function ($row) {
                $buttonClass = $row->status < 2 ? 'btn-primary' : 'btn-secondary';
                $buttonText = $row->status < 2 ? 'Bayar' : 'Lihat';
                $buttonIcon = $row->status < 2 ? 'fa-money-bill-wave' : 'fa-eye';
                return '<button class="btn btn-sm ' . $buttonClass . ' btn-detail" data-id="' . $row->no_invoice . '" data-status="' . $row->status . '" data-toggle="modal" data-target="#paymentModal"><i class="fas ' . $buttonIcon . ' fa-sm"></i> ' . $buttonText . '</button>';
            })
            ->filterColumn('list_lpb', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->whereExists(function ($subquery) use ($keyword) {
                        $subquery->select(DB::raw(1))
                            ->from('admin_lpb')
                            ->whereColumn('admin_lpb.no_invoice', 'invoice_lpb.no_invoice')
                            ->where('id_lpb', 'like', "%{$keyword}%");
                    })->orWhere('inv_jasa.no_jasa', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('tanggal_nota', function ($query, $keyword) {
                $query->where('invoice_lpb.tanggal', 'like', "%{$keyword}%");
            })
            ->filterColumn('kode_supplier', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('suppliers.nama', 'like', "%{$keyword}%")
                        ->orWhere('inv_jasa.nama', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['details_control', 'jenis_transaksi_label', 'status_pembayaran', 'action', 'tgl_deadline_pembayaran'])
            ->make(true);
    }

    public function getInvoiceCompositionJson($no_invoice)
    {
        $lpbHeaders = DB::table('admin_lpb')
            ->where('no_invoice', $no_invoice)
            ->select('id_lpb', 'no_po', 'no_sj', 'tanggal')
            ->orderBy('id_lpb', 'asc')
            ->get();

        if ($lpbHeaders->isEmpty()) {
            $jasaHeader = DB::table('inv_jasa')
                ->where('no_jasa', $no_invoice)
                ->select('no_jasa', 'tanggal', 'term_pengiriman as info_tambahan')
                ->first();

            if ($jasaHeader) {
                return response()->json([
                    'data' => [
                        [
                            'header' => [
                                'id_lpb' => $jasaHeader->no_jasa,
                                'no_po' => $jasaHeader->no_jasa,
                                'no_sj' => '-',
                                'tanggal' => $jasaHeader->tanggal
                            ],
                            'details' => [
                                [
                                    'nm_bahan' => 'Transaksi Jasa: ' . $jasaHeader->info_tambahan,
                                    'jumlah_barang_diterima' => 1,
                                    'lot_number' => '-'
                                ]
                            ]
                        ]
                    ]
                ]);
            }

            return response()->json(['data' => []]);
        }

        $structuredData = $lpbHeaders->map(function ($header) {
            $details = DB::table('admin_lpb_detail')
                ->join('bahan', 'admin_lpb_detail.id_bahan', '=', 'bahan.id')
                ->where('admin_lpb_detail.id_lpb', $header->id_lpb)
                ->select('bahan.nama as nm_bahan', 'admin_lpb_detail.jumlah_barang_diterima', 'admin_lpb_detail.lot_number')
                ->get();

            return ['header' => $header, 'details' => $details];
        });

        return response()->json(['data' => $structuredData]);
    }

    public function getDetailJson($no_invoice)
    {
        $invoice = DB::table('invoice_lpb')
            ->leftJoin('suppliers', 'invoice_lpb.kode_supplier', '=', 'suppliers.id')
            ->leftJoin('inv_jasa', 'invoice_lpb.no_invoice', '=', 'inv_jasa.no_jasa')
            ->where('invoice_lpb.no_invoice', $no_invoice)
            ->select('invoice_lpb.*', DB::raw('COALESCE(suppliers.nama, inv_jasa.nama) as nama_supplier'))
            ->first();

        if (!$invoice) {
            return response()->json(['error' => 'Invoice tidak ditemukan'], 404);
        }

        $riwayat = DB::table('pembayaran_transaksi_detail')
            ->where('id_invoice_lpb', $invoice->id)
            ->orderBy('tanggal_pembayaran', 'desc')
            ->get();

        return response()->json(['invoice' => $invoice, 'riwayat' => $riwayat]);
    }

    public function storePembayaran(Request $request)
    {
        $request->validate([
            'no_invoice' => 'required|string|exists:invoice_lpb,no_invoice',
            'transactions' => 'required|array|min:1',
            'transactions.*.jenis_bayar' => 'required|integer',
            'transactions.*.tanggal' => 'required|date',
            'transactions.*.pembayaran' => 'required|numeric|min:0',
        ]);

        $noInvoice = $request->input('no_invoice');
        $transactions = $request->input('transactions');

        $invoice = DB::table('invoice_lpb')->where('no_invoice', $noInvoice)->first();
        if (!$invoice) {
            return response()->json(['error' => 'Invoice tidak ditemukan.'], 404);
        }

        DB::beginTransaction();
        try {
            foreach ($transactions as $trans) {
                $dataToInsert = [
                    'id_invoice_lpb' => $invoice->id,
                    'tanggal_pembayaran' => $trans['tanggal'],
                    'jumlah_pembayaran' => 0,
                    'potongan_materai' => 0,
                    'biaya_transfer_bank' => 0,
                    'selisih_bayar' => 0,
                    'potongan_pph23' => 0,
                    'metode_pembayaran' => $this->getMetodeBayarText($trans['jenis_bayar']),
                    'id_user_finance' => session('user_data')['id'],
                    'created_at' => now(),
                ];

                switch ((int) $trans['jenis_bayar']) {
                    case 0:
                        $dataToInsert['jumlah_pembayaran'] = $trans['pembayaran'];
                        break;
                    case 1:
                        $dataToInsert['potongan_materai'] = $trans['pembayaran'];
                        break;
                    case 2:
                        $dataToInsert['biaya_transfer_bank'] = $trans['pembayaran'];
                        break;
                    case 3:
                        $dataToInsert['selisih_bayar'] = $trans['pembayaran'];
                        break;
                    case 4:
                        $dataToInsert['potongan_pph23'] = $trans['pembayaran'];
                        break;
                }
                DB::table('pembayaran_transaksi_detail')->insert($dataToInsert);
            }

            $totalPembayaran = DB::table('pembayaran_transaksi_detail')
                ->where('id_invoice_lpb', $invoice->id)
                ->sum(DB::raw('COALESCE(jumlah_pembayaran,0) + COALESCE(potongan_materai,0) + COALESCE(biaya_transfer_bank,0) + COALESCE(selisih_bayar,0) + COALESCE(potongan_pph23,0)'));

            $sisaTagihan = $invoice->grand_total - $totalPembayaran;
            $statusPembayaran = $sisaTagihan <= 0.01 ? 'Lunas' : ($totalPembayaran > 0 ? 'Dibayar Sebagian' : 'Belum Dibayar');
            $statusInvoice = $statusPembayaran == 'Lunas' ? 2 : 1;

            DB::table('invoice_lpb')
                ->where('id', $invoice->id)
                ->update([
                    'total_pembayaran' => $totalPembayaran,
                    'status_pembayaran' => $statusPembayaran,
                    'status' => $statusInvoice,
                    'updated_at' => now(),
                ]);

            DB::commit();
            return response()->json(['success' => 'Semua transaksi berhasil disimpan!']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal simpan multi-transaksi: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan pada server saat menyimpan data. Error: ' . $e->getMessage()], 500);
        }
    }

    private function getMetodeBayarText($jenis_bayar)
    {
        $map = [0 => 'Pembayaran Supplier', 1 => 'Potongan Materai', 2 => 'Biaya Bank', 3 => 'Selisih Bayar', 4 => 'PPh 23'];
        return $map[$jenis_bayar] ?? 'Lainnya';
    }

    public function destroyDetail($id)
    {
        try {
            DB::beginTransaction();

            $detail = DB::table('pembayaran_transaksi_detail')->where('id', $id)->first();
            if (!$detail) {
                return response()->json(['error' => 'Data tidak ditemukan'], 404);
            }

            $invoiceId = $detail->id_invoice_lpb;

            DB::table('pembayaran_transaksi_detail')->where('id', $id)->delete();

            $totalBayar = DB::table('pembayaran_transaksi_detail')
                ->where('id_invoice_lpb', $invoiceId)
                ->sum(DB::raw('COALESCE(jumlah_pembayaran,0) + COALESCE(potongan_materai,0) + COALESCE(biaya_transfer_bank,0) + COALESCE(selisih_bayar,0) + COALESCE(potongan_pph23,0)')) ?? 0;

            $invoice = DB::table('invoice_lpb')->where('id', $invoiceId)->first();

            $sisaVirtual = $invoice->grand_total - $totalBayar;

            $status_pembayaran = 'Belum Dibayar';
            if ($sisaVirtual <= 0.01) {
                $status_pembayaran = 'Lunas';
            } elseif ($totalBayar > 0) {
                $status_pembayaran = 'Dibayar Sebagian';
            }

            $statusInt = ($sisaVirtual <= 0.01) ? 2 : ($totalBayar > 0 ? 1 : 0);

            DB::table('invoice_lpb')->where('id', $invoiceId)->update([
                'total_pembayaran' => $totalBayar,
                'status_pembayaran' => $status_pembayaran,
                'status' => $statusInt,
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['success' => 'Transaksi berhasil dihapus']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportmutu(Request $request)
    {
        $dari = $request->input('dari') ?: now()->startOfMonth()->format('Y-m-d');
        $sampai = $request->input('sampai') ?: now()->format('Y-m-d');

        $namaFile = 'Laporan_Mutu_Supplier_nonkertas' . $dari . '_sd_' . $sampai . '.xlsx';
        return Excel::download(new PembayaranSuplierExport($dari, $sampai), $namaFile);
    }
}
