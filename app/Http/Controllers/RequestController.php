<?php

namespace App\Http\Controllers;

use App\Models\Request as RequestModel;
use App\Models\RequestDetail;
use App\Models\Bahan;
use App\Models\AdminNamagudang;
use App\Http\Requests\StorerequestRequest;
use App\Http\Requests\UpdaterequestRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\DocumentNumberService;
use App\Models\KategoriBahan;
use Illuminate\Validation\ValidationException;

class RequestController extends Controller
{
    public function __construct(private DocumentNumberService $numbers) {}
    public function index(Request $request)
    {
        $this->authorize('viewAny', RequestModel::class);

        if ($request->ajax()) {
            $status = $request->input('status');

            $query = RequestModel::with(['details'])
                ->when(!empty($status), function ($q) use ($status) {
                    return $q->where('status', $status);
                });

            return datatables()->of($query)
                ->addColumn('formatted_date', function ($row) {
                    return $row->created_at ? $row->created_at->format('d-m-Y H:i') : '-';
                })
                ->addColumn('can_approve', function ($row) use ($request) {
                    return strtolower((string) $row->status) === 'pending' && $request->user()->can('approve', $row);
                })
                ->make(true);
        }

        return view('request.index');
    }

    public function reportPdf(Request $request)
    {
        $this->authorize('viewAny', RequestModel::class);

        $filters = collect($request->input('filters', []))->filter(fn($value) => $value !== '');
        $search = trim((string) $request->input('search', ''));
        $query = RequestModel::query()
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->latest();

        if ($search !== '') {
            $query->where(fn($q) => $q->where('no_request', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhere('created_at', 'like', "%{$search}%"));
        }

        foreach (['no_request', 'status', 'created_at'] as $field) {
            if ($filters->has($field)) {
                $query->where($field, 'like', "%{$filters[$field]}%");
            }
        }

        $rows = $query->limit(5000)->get()->map(fn($row) => [
            'no_request' => $row->no_request,
            'status' => ucfirst($row->status),
            'created_at' => optional($row->created_at)->format('d-m-Y H:i'),
        ]);

        return Pdf::loadView('reports.table-pdf', [
            'title' => 'Daftar Request',
            'columns' => [
                ['key' => 'no_request', 'label' => 'No Request', 'align' => 'left'],
                ['key' => 'status', 'label' => 'Status', 'align' => 'left'],
                ['key' => 'created_at', 'label' => 'Tanggal Request', 'align' => 'left'],
            ],
            'rows' => $rows,
            'search' => $search,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->stream('daftar-request.pdf');
    }

    public function create()
    {
        $this->authorize('create', RequestModel::class);

        $bahans = Bahan::orderBy('nama', 'asc')->get();
        $gudangs = AdminNamagudang::all();
        $kategoris = KategoriBahan::orderBy('katnama')->get();
        $documentNumber = $this->numbers->internal('REQ', 'PO');

        return view('request.create', compact('bahans', 'gudangs', 'kategoris', 'documentNumber'));
    }

    public function store(StorerequestRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $reqHeader = RequestModel::create([
                'no_request' => $validated['no_request'],
                'status'     => 'pending',
            ]);

            foreach ($validated['items'] as $item) {
                RequestDetail::create([
                    'request_id'   => $reqHeader->id,
                    'bahan_id'     => $item['bahan_id'] ?? null,
                    'nama_barang'  => $item['nama_barang'],
                    'jumlah_minta' => $item['jumlah_minta'],
                    'keterangan'   => $item['keterangan'] ?? null,
                    'realisasi'    => 0,
                    'kategori'     => $item['kategori'] ?? null,
                    'satuan'       => $item['satuan'] ?? null,
                    'berat_kecil'  => $item['berat_kecil'] ?? 1.00,
                    'satuan_kecil' => $item['satuan_kecil'] ?? null,
                    'tipe_gudang'  => $item['tipe_gudang'] ?? null,
                    'tipe_barang'  => $item['tipe_barang'] ?? null,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Request berhasil dibuat.'
        ]);
    }

    public function approveForm(RequestModel $request)
    {
        $this->authorize('approve', $request);

        $requestData = $request->load(['details.bahan', 'details.kategoriBahan', 'details.gudang']);

        return view('request.approve', compact('requestData'));
    }

    public function processApprove(UpdaterequestRequest $formRequest, RequestModel $request)
    {
        $validated = $formRequest->validated();
        $user = Auth::user();

        DB::transaction(function () use ($validated, $request, $user) {
            foreach ($validated['items'] as $detailId => $itemData) {
                $detail = RequestDetail::findOrFail($detailId);

                if (!$detail->bahan_id) {
                    $this->authorize('create', Bahan::class);
                    $category = KategoriBahan::findOrFail($detail->tipe_barang);
                    if (!$category->coa_persediaan_id || !$category->coa_beban_id || !$category->coa_clearing_lpb_id) {
                        throw ValidationException::withMessages([
                            'kategori' => "Kategori {$category->katnama} belum memiliki mapping COA persediaan, beban, dan GRNI yang lengkap.",
                        ]);
                    }
                    $newBahan = Bahan::create([
                        'nama'                 => $detail->nama_barang,
                        'keterangan_bahan'     => $detail->keterangan,
                        'satuan'               => $detail->satuan,
                        'kategori'             => $detail->tipe_barang,
                        'berat_kecil'          => $detail->satuan_kecil ? $detail->berat_kecil : 1.00,
                        'satuan_kecil'         => $detail->satuan_kecil,
                        'tipe_gudang'          => $detail->tipe_gudang,
                        'tipe_barang'          => $detail->tipe_barang,
                        'stok_onhand'          => 0,
                        'stok_onpurchase'      => 0,
                        'planning'             => 0,
                        'stokawal'             => 0,
                        'pengambilan_stokawal' => 0,
                    ]);

                    $detail->bahan_id = $newBahan->id;
                }

                $detail->update([
                    'jumlah_acc' => $itemData['jumlah_acc'],
                ]);
            }

            $request->update([
                'status'           => 'approved',
                'catatan_approver' => $validated['catatan_approver'] ?? null,
                'approved_by'      => $user->id,
                'approved_at'      => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Request berhasil disetujui.'
        ]);
    }
}
